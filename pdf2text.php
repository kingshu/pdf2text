<?php
class pdf2text {
	
	protected $text = "";
	private $randName =	"";
	private $filename = "";

	public function __construct($file){
		// sanitize filename before assign
		$this->filename = preg_replace ("/ /", "\ ", $file);
		$this->randName = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 3);
	}
	
	/*
	 * method getText()
	 * Returns all text in the PDF as flat plaintext. Removes special characters and whitespaces
	 */
	public function getText(){
		$this->text = $this->pdftotext($this->filename);
		$this->text = str_replace("\n", "%NEWLINE#%", $this->text);
		$this->text = str_replace("\t", "%TAB#%", $this->text);
		$this->text = str_replace("\f", "%F#%", $this->text);
		$this->text = preg_replace( '/[^[:print:]]/', ' ', $this->text);
		$this->text = str_replace("%NEWLINE#%", " \n", $this->text);
		$this->text = str_replace("%TAB#%", " \t", $this->text);
		$this->text = str_replace("%F#%", " \f", $this->text);
		$count = 1;
		//$this->text = str_replace("\n", " ", $this->text);
		while($count > 0){
			$this->text = str_replace("  ", " ", $this->text, $count);
		}
		$this->text = str_replace("- ", "", $this->text, $count);
		$this->text = str_replace(" -", "-", $this->text, $count);
	 
	 	return $this->text;
	}
	
	public function getKeyWords($count = 10, $delim = " "){
		return implode(   $delim,  array_keys(  searchHelper::getKeyWords( $this->text, $count )  )   );
	}
	
	
	
	
	/*
	 * method getValue()
	 * Returns as string, the value of any field, given the corresponding FieldName. NULL for empty field
	 * @param <string> $fieldname - Name of field whose value is required
	 */
	public function getValue($fieldname) {
		$tmp_fields = "tmp_pdf2text_fields.txt";
		$p = exec("pdftk $this->filename dump_data_fields > $tmp_fields");
		$subject = file_get_contents($tmp_fields);
		unlink($tmp_fields);
		$pattern = '/FieldName: '.$fieldname.'\n[^---]*FieldValue: (.*)/';
		if (preg_match($pattern, $subject, $matches) == 1)
			return $matches[1];
		else {
			echo "Field \"$fieldname\" is incomplete or does not exist.";
			return NULL;
		}
	}
	
	protected function pdftotext($filename){
		$tmp_text = "tmp_pdf2text.txt";
		$o = exec("pdftotext -raw -eol unix -enc Latin1 \"" . $filename . "\" \"" . $tmp_text . "\"");
		$c = file_get_contents($tmp_text);
		unlink($tmp_text);
		return preg_replace("@\x0D\x0A\x0D\x0A\.\x0D\x0A\x0D\x0A\.\x0D\x0A\x0D\x0A[^\x0D]+\x0D\x0A\x0D\x0A[^\x0D]+\x0D\x0A\x0D\x0A[^\x0D]+\x0D\x0A\x0D\x0A@"," ",$c);
	}
	
	
	
	/*
	 * method makeArray()
	 * Returns all data as an associative array of key-value pairs. NULL for empty field
	 */	
	public function makeArray() {
		$tmp_fields = "tmp_pdf2text_fields_ar.txt";
		$p = exec("pdftk $this->filename dump_data_fields > $tmp_fields");
		$data = array();
		$key = NULL;
		$val = NULL;
		foreach(file($tmp_fields) as $line) {
			if (preg_match('/FieldName: (.*)/', $line, $matches)==1) {
				$key = $matches[1];
			}
			if (preg_match('/FieldValue: (.*)/', $line, $matches)==1) {
				$val = $matches[1];
			}
			if (preg_match('/---/', $line, $matches)==1) {
				if($key != NULL)
					$data[$key] = $val;
				$val = NULL;
			}	
		}		
		unlink($tmp_fields);
		return $data;
	}
	
	
	
	/* method listFields()
	 * Returns all FieldNames in a 1D array.
	 */
	public function listFields () {
		$tmp_fields = "tmp_pdf2text_fields_list.txt";
		$p = exec("pdftk $this->filename dump_data_fields > $tmp_fields");
		$keys = array();
		foreach(file($tmp_fields) as $line) {
			if (preg_match('/FieldName: (.*)/', $line, $matches)==1) {
				array_push($keys, $matches[1]);
			}	
		}
		unlink($tmp_fields);
		return $keys;
	}
	
	/* method getImages()
	 * Extracts all images in the document and returns array of image file-names.
	 */
	public function getImages () {
		$pattern = '%([\w/]+)/[\w]+\.pdf%';
		if (preg_match($pattern, $this->filename, $matches) == 1)
			$dir = $matches[1];
		
		$p = exec("pdfimages -j $this->filename $dir/$this->randName");

		return glob("$dir/$this->randName*.jpg"); 
	}
	
	public function cleanImgs() {
		$pattern = '%([\w/]+)/[\w]+\.pdf%';
		if (preg_match($pattern, $this->filename, $matches) == 1)
			$dir = $matches[1];
	
		$imgs = array_merge (glob("$dir/$this->randName*.jpg"), glob("$dir/$this->randName*.ppm"));
		
		foreach ($imgs as $img) {	
			unlink ($img);
		}
	}
}
?> 

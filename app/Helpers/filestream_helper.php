<?php
/**
 * This file is part of the 247Commerce BigCommerce CASHFLOW App.
 *
 * ©247 Commerce Limited <info@247commerce.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
 /**
 * Class FileStream
 *
 * Represents a helper class to save or create folders and files in project directory
 */
class FileStream
{

	/* for Saving file in server */
	public static function saveFile($filename,$filecontent,$folderPath=''){
		if (strlen($filename)>0){
			if (!file_exists($folderPath)) {
				mkdir($folderPath);
			}
			$file = @fopen($folderPath . DIRECTORY_SEPARATOR . $filename,"w");
			if ($file != false){
					 //file_put_contents($folderPath . DIRECTORY_SEPARATOR . $filename, "$filecontent");
					  fwrite($file,$filecontent);
				fclose($file);
				return 1;
			}
			return -2;
		}
		return -1;
	}
}
<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#[\AllowDynamicProperties]
class Template{
	public function load_template($view_file_name,$data_array=array(),$session_data='',$loadheader = 1) {
		$ci = & get_instance();
		if($loadheader){
			$ci->load->view("layout/head");
			$ci->load->view("layout/header",$session_data);
			echo ' <div class="page-container">';
			$ci->load->view("layout/sidebar",$session_data);	
		}
		$tempViewFileName = explode("/",$view_file_name);
		krsort($tempViewFileName);
		$viewNameTemp = array_shift($tempViewFileName);
		$subappname = CLIENTCODE;
		$appname 	= APPNAME;		
		if($appname){
			$viewFound = 0;
			array_unshift($tempViewFileName,$appname);
			if($subappname){
				$tempViewFileName2 = $tempViewFileName;
				array_unshift($tempViewFileName2,$subappname);
				array_unshift($tempViewFileName2,$viewNameTemp);
				krsort($tempViewFileName2);
				$newView = implode("/",$tempViewFileName2);
				$result = glob(FCPATH. 'application'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$newView.'.*');
				if($result){
					$view_file_name = $newView;
					$viewFound = 1;
				}
				else{
					$result = glob(VIEWBSITC. $newView.'.*');
					if($result){
						$view_file_name = $newView;
						$viewFound = 1;
					}
				}
			}
			if(!$viewFound){
				array_unshift($tempViewFileName,$viewNameTemp);
				krsort($tempViewFileName);
				$newView = implode("/",$tempViewFileName);
				$result = glob(FCPATH. 'application'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$newView.'.*');
				if($result){
					$view_file_name = $newView;
				}
				else{
					$result = glob(VIEWBSITC. $newView.'.*');
					if($result){
						$view_file_name = $newView;
					}
				}
				
			}
		}
		$ci->load->view($view_file_name,$data_array);
		if($loadheader){
			echo '</div>';	
			$ci->load->view("layout/footer",$session_data);
		}
	}	
	
	
	
}
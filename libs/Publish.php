<?php 

class Publish
{

	// publishes the entire site
	public static function PublishSite($siteUniqId, $root = '../'){
		
		// publish sitemap
		Publish::PublishSiteMap($siteUniqId, $root);
		
		// publish all CSS
		Publish::PublishAllCSS($siteUniqId, $root);	

		// publish all pages
		Publish::PublishAllPages($siteUniqId, $root);

		// publish rss for page types
		Publish::PublishRssForPageTypes($siteUniqId, $root);
		
		// publish menu
		Publish::PublishMenu($siteUniqId, $root);
		
		// publish common js
		Publish::PublishCommonJS($siteUniqId, $root);
		
		// publish common css
		Publish::PublishCommonCSS($siteUniqId, $root);
		
		// publish controller
		Publish::PublishHtaccess($siteUniqId, $root);

		// publish plugins
		Publish::PublishPlugins($siteUniqId, $root);
	}
	
	// publishes the controller
	public static function PublishHtaccess($siteUniqId, $root = '../'){
        
        $site = Site::GetBySiteUniqId($siteUniqId);
        
		$src = $root.'sites/common/.htaccess';
		$dest = $root.'sites/'.$site['FriendlyId'].'/.htaccess';
		
		copy($src, $dest); // copy the controller
	}

	// publishes plugins
	public static function PublishPlugins($siteUniqId, $root = '../'){
		
		$site = Site::GetBySiteUniqId($siteUniqId);
		
		// create plugin directory
		$dest = $root.'sites/'.$site['FriendlyId'].'/plugins';
		
		// create dir if it doesn't exist
		if(!file_exists($dest)){
			mkdir($dest, 0755, true);	
		}
		
		$json = file_get_contents('../plugins/plugins.json');
		$data = json_decode($json, true);

		foreach($data as &$item) {
			$type = $item['type'];

			$p_src = $root.'plugins/'.$type.'/deploy';

			if(file_exists($p_src)){

				$p_dest = $root.'sites/'.$site['FriendlyId'].'/plugins/'.$type;

				if(!file_exists($p_dest)){
					mkdir($p_dest, 0755, true);	
				}

				Utilities::CopyDirectory($p_src, $p_dest);
			}

		}
		
	}

	// publishes a theme
	public static function PublishTheme($site, $theme, $root='../'){

		$theme_dir = $root.'sites/'.$site['FriendlyId'].'/themes/';
		
		// create themes
		if(!file_exists($theme_dir)){
			mkdir($theme_dir, 0755, true);	
		}
		
		// create directory for theme
		$theme_dir .= $theme .'/';
		
		if(!file_exists($theme_dir)){
			mkdir($theme_dir, 0755, true);	
		}
		
		// create directory for layouts
		$layouts_dir = $theme_dir.'/layouts/';
		
		if(!file_exists($layouts_dir)){
			mkdir($layouts_dir, 0755, true);	
		}
		
		// create directory for styles
		$styles_dir = $theme_dir.'/styles/';
		
		if(!file_exists($styles_dir)){
			mkdir($styles_dir, 0755, true);	
		}

		// copy layouts
		$layouts_src = $root.'themes/'.$theme.'/layouts/';
		$layouts_dest = $root.'sites/'.$site['FriendlyId'].'/themes/'.$theme.'/layouts/';

		Utilities::CopyDirectory($layouts_src, $layouts_dest);
		
		// copy styles
		$styles_src = $root.'themes/'.$theme.'/styles/';
		$styles_dest = $root.'sites/'.$site['FriendlyId'].'/themes/'.$theme.'/styles/';
		
		Utilities::CopyDirectory($styles_src, $styles_dest);
		
		// copy files
		$files_src = $root.'themes/'.$theme.'/files/';
		$files_dest = $root.'sites/'.$site['FriendlyId'].'/files/';

		Utilities::CopyDirectory($files_src, $files_dest);
	}
	
	
	// publishes common js
	public static function PublishCommonJS($siteUniqId, $root = '../'){
		
		$site = Site::GetBySiteUniqId($siteUniqId);
		
		$src = $root.'sites/common/js';
		$dest = $root.'sites/'.$site['FriendlyId'].'/js';
		
		// create dir if it doesn't exist
		if(!file_exists($dest)){
			mkdir($dest, 0755, true);	
		}
		
		// copies a directory
		Utilities::CopyDirectory($src, $dest);
	}
	
	// publishes common css
	public static function PublishCommonCSS($siteUniqId, $root = '../'){
		
		$site = Site::GetBySiteUniqId($siteUniqId);
		
		$src = $root.'sites/common/css';
		$dest = $root.'sites/'.$site['FriendlyId'].'/css';
		
		// create dir if it doesn't exist
		if(!file_exists($dest)){
			mkdir($dest, 0755, true);	
		}
		
		// copies a directory
		Utilities::CopyDirectory($src, $dest);
	}
	
	// publishes all the pages in the site
	public static function PublishAllPages($siteUniqId, $root = '../'){
	
		$site = Site::GetBySiteUniqId($siteUniqId);
		
		// Get all pages
		$list = Page::GetPagesForSite($site['SiteId']);
		
		foreach ($list as $row){
			Publish::PublishPage($row['PageUniqId'], false, $root);
		}
	}
	
	// publish menu
	public static function PublishMenu($siteUniqId, $root = '../'){
		
		$site = Site::GetBySiteUniqId($siteUniqId);
		
		$list = MenuItem::GetMenuItems($site['SiteId']);
		
		$menu = array();
		$count = 0;
		
		foreach ($list as $row){

			if($row['PageId']!=-1){
			
				$page = Page::GetByPageId($row['PageId']);

				if($page != null){
					$pageUniqId = $page['PageUniqId'];
				}
				else{
					$pageUniqId = -1;
				}
			}
			else{
				$pageUniqId = -1;
			}

			$item = array(
					'MenuItemUniqId' => $row['MenuItemUniqId'],
				    'Name'  => $row['Name'],
				    'CssClass'  => $row['CssClass'],
				    'Type' => $row['Type'],
					'Url' => $row['Url'],
					'PageUniqId' => $pageUniqId
				);
			$menu[$count] = $item;	
			$count = $count + 1;
		}
		
		// encode to json
		$encoded = json_encode($menu);

		$dest = $root.'sites/'.$site['FriendlyId'].'/data/';
		
		Utilities::SaveContent($dest, 'menu.json', $encoded);
	}
	
	// publish rss for all page types
	public static function PublishRssForPageTypes($siteUniqId, $root = '../'){
		
		$site = Site::GetBySiteUniqId($siteUniqId);
		
		$list = PageType::GetPageTypes($site['SiteId']);
		
		foreach ($list as $row){
			Publish::PublishRssForPageType($siteUniqId, $row['PageTypeId'], $root);
		}
	}
	
	// publish rss for pages
	public static function PublishRssForPageType($siteUniqId, $pageTypeId, $root = '../'){
		
		$site = Site::GetBySiteUniqId($siteUniqId);
		
		$dest = $root.'sites/'.$site['FriendlyId'];
		
		$pageType = PageType::GetByPageTypeId($pageTypeId);
		
		// generate rss
		$rss = Utilities::GenerateRSS($site, $pageType);
		
		Utilities::SaveContent($dest.'/data/', strtolower($pageType['TypeP']).'.xml', $rss);
	}
	
	// publish sitemap
	public static function PublishSiteMap($siteUniqId, $root = '../'){
		
		$site = Site::GetBySiteUniqId($siteUniqId);
		
		$dest = $root.'sites/'.$site['FriendlyId'];
		
		// generate default site map
		$content = Utilities::GenerateSiteMap($site);
		
		Utilities::SaveContent($dest.'/', 'sitemap.xml', $content);
	}
	
	// publishes a specific css file
	public static function PublishCSS($site, $name, $root = '../'){
	
		// get references to file
	    $lessDir = $root.'sites/'.$site['FriendlyId'].'/themes/'.$site['Theme'].'/styles/';
	    $cssDir = $root.'sites/'.$site['FriendlyId'].'/css/';

	    $lessFile = $lessDir.$name.'.less';
	    $cssFile = $cssDir.$name.'.css';

	    // create css directory (if needed)
	    if(!file_exists($cssDir)){
			mkdir($cssDir, 0755, true);	
		}

	    if(file_exists($lessFile)){
	    	$content = file_get_contents($lessFile);

	    	$less = new lessc;

	    	try{
			  $less->checkedCompile($lessFile, $cssFile);

			  return true;
			} 
			catch(exception $e){
			  return false;
			}
    	}
    	else{
    		return false;
    	}

	}

	
	// publishes all css
	public static function PublishAllCSS($siteUniqId, $root = '../'){

		$site = Site::GetBySiteUniqId($siteUniqId); // test for now

		$lessDir = $root.'sites/'.$site['FriendlyId'].'/themes/'.$site['Theme'].'/styles/';
		
		//get all image files with a .less ext
		$files = glob($lessDir . "*.less");

		//print each file name
		foreach($files as $file){
			$f_arr = explode("/",$file);
			$count = count($f_arr);
			$filename = $f_arr[$count-1];
			$name = str_replace('.less', '', $filename);

			Publish::PublishCSS($site, $name, $root);
		}

	}

	// publishes a fragment
	public static function PublishFragment($siteFriendlyId, $pageUniqId, $status, $content, $root = '../'){

		// clean content
		$content = str_replace( "&nbsp;", ' ', $content);

		$dir = $root.'sites/'.$siteFriendlyId.'/fragments/'.$status.'/';

		if(!file_exists($dir)){
			mkdir($dir, 0755, true);	
		}
		
		// create fragment
		$fragment = $root.'sites/'.$siteFriendlyId.'/fragments/'.$status.'/'.$pageUniqId.'.html';
		file_put_contents($fragment, $content); // save to file
	}
	
	// publishes a rendered version of the content
	public static function PublishRender($site, $page, $root = '../'){
	
		// create dir if need be
		$dir = $root.'sites/'.$site['FriendlyId'].'/fragments/render/';

		if(!file_exists($dir)){
			mkdir($dir, 0755, true);	
		}
		
		// get content from published fragment
		$content = '';
		$fragment = $root.'sites/'.$site['FriendlyId'].'/fragments/publish/'.$page['PageUniqId'].'.html';
    
        if(file_exists($fragment)){
          $content = file_get_contents($fragment);
        }
        
        $preview = false;
		
		// run the content through the parser
		$content = Utilities::ParseHTML($site, $page, $content, $preview, $root);
		
		// create fragment
		$fragment = $root.'sites/'.$site['FriendlyId'].'/fragments/render/'.$page['PageUniqId'].'.html';
		file_put_contents($fragment, $content); // save to file
	}

	// publishes a page
	public static function PublishPage($pageUniqId, $preview = false, $root = '../'){
	
		$page = Page::GetByPageUniqId($pageUniqId);
        
		if($page!=null){
			
			$site = Site::GetBySiteId($page['SiteId']); // test for now
			$dest = $root.'sites/'.$site['FriendlyId'].'/';
			$imageurl = $dest.'files/';
			$siteurl = 'http://'.$site['Domain'].'/';
			
			$friendlyId = $page['FriendlyId'];
			
			$url = '';
			$file = '';
            
            if($preview==true){
                $previewId = uniqid();
                
                $file = $page['FriendlyId'].'-'.$previewId.'-preview.php';
            }   
            else{
	 	  	    $file = $page['FriendlyId'].'.php';
            }
			
			// create a nice path to store the file
			if($page['PageTypeId']==-1){
				$url = $page['FriendlyId'].'.php';
				$path = '';
			}
			else{
				$pageType = PageType::GetByPageTypeId($page['PageTypeId']);
				
				$path = 'uncategorized/';
				
				if($pageType!=null){
					$path = strtolower($pageType['FriendlyId']).'/';
				}
	
			}
		
			// generate default
			$html = Utilities::GeneratePage($site, $page, $siteurl, $imageurl, $preview, $root);
            
            if($preview == true){
                 $s_dest = $dest.'preview/';
            }
            else{
			    $s_dest = $dest.$path;
            }
        
			// save the content to the published file
			Utilities::SaveContent($s_dest, $file, $html);
            
            // publish a rendered fragment
            Publish::PublishRender($site, $page, $root);
            
            return $s_dest.$file;
		}
	}
}

?>
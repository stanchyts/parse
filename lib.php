<?php
class parser{
	private $urlsBySite;
	private $oldUrlsBySite;
	private $flag;
	private $social;
	private $countByImg;
	
	public function __construct(){
		$this->oldUrlsBySite = array();
        $this->urlsBySite = array();
		$this->flag = true;
		$this->social = array("twitter.com", "facebook.com", "instagram.com", "vk.com", "mailto");
		$this->countByImg = 0;
	}
	
	private function getImages($url) {
		$html = @file_get_contents($url);
		preg_match_all('/<img[^>]+>/i',$html, $imgTags); 
		$srcByImg = array();
		for($i = 0; $i < count($imgTags[0]); $i++) {
            preg_match_all('/src=("[^"]*")/i',$imgTags[0][$i], $srcByImg[$i]);
		}
		return $srcByImg;
	}
	private function streamToFile($url, $mas) {
		$domain = parse_url($url);
		$nameByFile = $domain['host'].".csv";
		$fd = fopen($nameByFile, 'a') or die("we can't make file!");
        $str = "";
        for($i = 0; $i < count($mas); $i++) {
            $str = $str.$url.", ".$mas[$i][1][0]."\r\n";
		}
		fwrite($fd, $str);
        fclose($fd);
	}
	
	private function validateProtocol($url) {
	    $validateProtocol = strstr($url, "http");
        if ($validateProtocol === false) {
		   $url = "http://".$url;
		}
		return $url;
	}
	
	private function createLog($url) {
	    $domain = parse_url($url);
		$nameByFile = $domain['host'].".log";
		$fd = fopen($nameByFile, 'a') or die("we can't make file!");
	    $str = "pages: ".count($this->oldUrlsBySite)."; images:".$this->countByImg.".";
		fwrite($fd, $str);
        fclose($fd);
	}
	
	public function start($url) {
		$url = $this->validateProtocol($url);
		$html = @file_get_contents($url);
		if($html) {
		    $img = $this->getImages($url);
		    $this->streamToFile($url,$img);
		    array_push($this->oldUrlsBySite, $url);
		    var_dump($this->oldUrlsBySite);
		    $domain = parse_url($url);
		    preg_match_all('/<a[^>]+>/i', $html, $aTags);
            $href = array();
            for($i = 0; $i < count($aTags[0]); $i++) {
                preg_match_all('/href=("[^"]*")/i',$aTags[0][$i], $href[$i]);
			}				
            $urlsByPage = array();
            for($i = 0; $i < count($href); $i++) {
                $empty = @stripos($href[$i][1][0], $domain['host']);
                if ($empty !== false) {
			        $href[$i][1][0] = str_replace('"','',$href[$i][1][0]);
	                array_push($urlsByPage, $href[$i][1][0]);
		        }
	        }
		    if($this->flag) {
			    $this->urlsBySite = array_unique($urlsByPage);
			    $this->flag = false;
		    }
		    else {
		        $this->urlsBySite = array_merge($this->urlsBySite,array_unique($urlsByPage));
			}
		    foreach($this->urlsBySite as $key => $value) {
		        for($i = 0; $i < count($this->social); $i++) {
				    $empty = stripos($value, $this->social[$i]);
                    if ($empty !== false) {
				        unset($this->urlsBySite[$key]);
					}
			    }
			}
		    foreach($this->urlsBySite as $key => $value) {
		        for($i = 0; $i < count($this->oldUrlsBySite); $i++) {
				    if($value == $this->oldUrlsBySite[$i]) {
					    unset($this->urlsBySite[$key]);
					}
			}
            foreach($this->urlsBySite as $key => $value) {
		        $this->start($value); 
			}
			if(count($this->urlsBySite) == 0) {
			    echo "File with images: ".$domain['host'].".csv"; 
				$this->createLog($url);
			}				
		}
        else {
			echo "url is not availabel!\n";
		}
	}
	public function getStat($url) {
		$domain = parse_url($url);
		$lines = @file($domain['host'].'.log');
		if ($lines != false) {
            echo $lines[0];
		}
		else {
			echo "this domain has not been analyzed!";
		}
	}
}

class console {
	public function __construct(){
	}
	public function start($choose,$url){
		$prs = new parser();
		switch($choose) {
			case "parse": $prs -> start($url); break;
			case "report": $prs -> getStat($url); break;
			case "help": echo "/parse - start parser at the entered url\n/report - view domain statistics\n/help - view existing commands\n"; break;
			default: echo "Enter the command correctly (help)!"; break;
		}
	}
}

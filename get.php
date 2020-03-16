<?php

require "common/functions/retirarAcento.php";

function tdrows($elements) {
    $str = "";
    foreach ($elements as $element) {
        print_r($element);
        
        $str .= $element->nodeValue . ", ";
        
    }

    return $str;
}

function getdata($file) {
    
    //getting path
    $path = explode('/', $file);
    array_pop($path);
    $path = implode('/', $path)."/"; 
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $arr_cont = array();
            $lines = 0;

            $contents = file_get_contents("a.html");
            $DOM = new DOMDocument;
            @$DOM->loadHTML($contents);

            $items = $DOM->getElementsByTagName('tr');
            foreach ($items as $node) {
                foreach ($node->childNodes as $nodechild) {
                    if ($nodechild->nodeName == "th") {
                        $arr_cont[$lines]["head"] = nl2br($nodechild->nodeValue);
                    } else {
                        $arr_cont[$lines]["content"] = nl2br($nodechild->nodeValue);
                    }
                }
                ++$lines;
            }
        }
    }
}

function handlefiles($tmp, $lines, $action = "") {
    if ($action == "d") {
        die("aaa");
    } else {
        $handle = fopen($tmp, "a");
        if ($handle) {
	        fwrite($handle, $lines);
	        fclose($handle);
        }
    }
}

function getlinks($url, &$output = "", $pag = 1) {
    $lines = "";
    $pg_total = 0;
    $get_url = $url."&page=".$pag;

    $handle = @fopen($get_url, "r");
    if ($handle) { 
        while (!feof($handle)) {
            $buffer = fgets($handle, 4096);
            
            //<a href="/vufind/Record/PUC_SP-1_ed941951cbe64ffd9ded972c935d3fe7" class="title getFull" data-view="full">
            $pattern[0] = "/<a href=\"(\/vufind\/Record\/(.*))\" class=\"title getFull\" data-view=\"full\">/";
            if (preg_match($pattern[0], $buffer, $arr)) {
                $lines .= $arr[2].";http://bdtd.ibict.br".$arr[1].";".$pag."\n";
            }

            //<a href="/vufind/Search/Results?lookfor=cotas&amp;type=AllFields&amp;page=165">[165]</a>
            $pattern[1] = "/<a href=\"\/vufind\/Search\/Results\?lookfor=(.*)&amp;type=AllFields&amp;page=([0-9]*)\">\[[0-9]*\]<\/a>/";
            if (preg_match($pattern[1], $buffer, $arr)) {
                $pg_total = $arr[2];
                if ($output == "") {
                    $aux = retirarAcento(urldecode($arr[1]));
                    mkdir("./output/".$aux);
                    $output = "./output/".$aux."/".date("Y-m-d")."-".$aux.".csv";
                }
            }
        }        
    }
    handlefiles($output, $lines);
    if ($pag < $pg_total) {
        getlinks($url, $output, ++$pag);
    }
    sleep(5);
}
//getlinks("http://bdtd.ibict.br/vufind/Search/Results?lookfor=ded%C3%A3o&type=AllFields", $file);
$file = "./output/dedao/2020-03-13-dedao.csv";
getdata($file);

?>


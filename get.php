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

function escape($m) {
	return "\"".str_replace(array(";", "\""), array(",", "'"), trim($m))."\"; ";
}


function exportdata($arr_cont, $current_ln, $file) {
    
    //getting path
    $path = explode('/', $file);
    $aux_file = $path[3];
    
    array_pop($path);
    $path = implode('/', $path)."/"; 
    
    //getting file name
    $aux_file = explode('.', $aux_file);
    $file = $aux_file[0]."_conteudo.csv";
    
    //exporting data
    $handle = fopen($path.$file, "a");
    if ($handle) {
        $head = "";
        $content = "";
        $cnt_field = 0;
        foreach ($arr_cont as $arr_ch_cont) {
            //if ($cnt_field == 12) {
            //    handledownload($cnt_field["content"]);    
            //}
            if ($current_ln < 1) {
                $head .= str_replace(":", "", escape($arr_ch_cont["head"]));
            } 
            $content .= escape($arr_ch_cont["content"]);
            ++$cnt_field;
        }
        if ($current_ln < 1) {
            fwrite($handle, substr($head, 0 , -2)."\n");
        }
        fwrite($handle, substr($content, 0 , -2)."\n");
        fclose($handle);
    }
    
}

function getdata($file) {
    if (($handle = fopen($file, "r")) !== FALSE) {
        $lines = 0;
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $arr_cont = array();
            $cnt_ln = 0;
            
            $contents = file_get_contents(trim($data[1]));
            $aaa = fopen("./output/dedao/example/".$data[0].".html", "a");
            fwrite($aaa, $contents);
            fclose($aaa);
            $DOM = new DOMDocument;
            @$DOM->loadHTML($contents);

            $items = $DOM->getElementsByTagName('tr');
            foreach ($items as $node) {
                foreach ($node->childNodes as $nodechild) {
                    $arr_replace = array("\r", "\r\n", "<br>", "<br />", ">", "\t");
                    if ($nodechild->nodeName == "th") {
                        //$arr_cont[$cnt_ln]["head"] = trim(str_replace($arr_replace, "||", nl2br($nodechild->nodeValue)));
                        $arr_cont[$cnt_ln]["head"] = preg_replace('#\s+#', ' ', str_replace("\n", "|", $nodechild->nodeValue));                    
                    } else {
                        $arr_cont[$cnt_ln]["content"] = preg_replace('#\s+#', ' ', $nodechild->nodeValue);
                    }
                }
                ++$cnt_ln;
            }
            exportdata($arr_cont, $lines, $file);
            ++$lines;
        }
    }
    return true;
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
$file = "./output/dedao/2020-03-21-dedao.csv";
getdata($file);

?>


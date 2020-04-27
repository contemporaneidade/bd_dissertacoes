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

function exportdata2($arr_titles, $arr_cont) {
    //gambiarra para pegar todos os títulos
    $titulos = "";
    foreach ($arr_titles as $title) {
        $titulos .= $title."; ";        
    }
    
    echo substr($titulos, 0, -2)."\n";
    
    foreach ($arr_cont as $line) {
        $conteudo = "";
        foreach ($arr_titles as $cell) {
            if (isset($line[$cell])) {
                //die($line[$cell]);
                $conteudo .= escape(trim($line[$cell]));
            } else {
                //die($conteudo);
                $conteudo .= "-; ";
            }
        }
        echo substr($conteudo, 0, -2)."\n";
    }
    
}

function getdata($file) {
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $lines = 0;
        $arr_cont = array();
        $arr_titles = array();
        
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            //fazer uma função pra isso depois pq esse código se repete
            $aux_file_name = preg_split( "/(\/|-|\.)/", $file);

            $output_file_name = $aux_file_name[sizeof($aux_file_name)-2]."-".$data[2]."-".$data[0].".html";
           
            $aux_path = explode('/', $file);
            array_pop($aux_path);
            $path = implode('/', $aux_path)."/";

            $output_path = $path."conteudo";
            //fim
            
    
            $cnt_ln = 0;
            
            $contents = file_get_contents($output_path."/".$output_file_name);
            
            $DOM = new DOMDocument;
            @$DOM->loadHTML($contents);
            $items = $DOM->getElementsByTagName('tr');
            foreach ($items as $node) { //linha
                $title = "";
                foreach ($node->childNodes as $nodechild) { //célula
                    $arr_replace = array("\r", "\r\n", "<br>", "<br />", ">", "\t");
                    if ($nodechild->nodeName == "th") {
                        $title = substr(preg_replace('#\s+#', ' ', str_replace("\n", "|", $nodechild->nodeValue)), 0, -1);
                        $arr_titles[$title] = $title;
                        //$arr_cont[$cnt_ln]["head"] = trim(str_replace($arr_replace, "||", nl2br($nodechild->nodeValue)));
                        //$arr_cont[$cnt_ln]["head"] = preg_replace('#\s+#', ' ', str_replace("\n", "|", $nodechild->nodeValue));     
                    } else {
                        //$arr_cont[$lines][$title]["content"] = preg_replace('#\s+#', ' ', $nodechild->nodeValue);
                        $arr_cont[$lines][$title] = preg_replace('#\s+#', ' ', $nodechild->nodeValue);
                    }
                }
                //++$cnt_ln;
            }
            
            ++$lines;
            //if ($lines == 100) {
            //    exportdata2($arr_titles, $arr_cont);
            //    exit;
            //}
            
            
        }
        exportdata2($arr_titles, $arr_cont);
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
    $html = ""; 
    $lines = "";
    $aux = "raca";
    $pg_total = 0;
    
    $get_url = $url."&page=".$pag;

    $handle = @fopen($get_url, "r");
    if ($handle) {
        while (!feof($handle)) {
            $buffer = fgets($handle, 4096);
            $html .= $buffer;

            //<a href="/vufind/Record/PUC_SP-1_ed941951cbe64ffd9ded972c935d3fe7" class="title getFull" data-view="full">
            $pattern[0] = "/<a href=\"(\/vufind\/Record\/(.*))\" class=\"title getFull\" data-view=\"full\">/";
            if (preg_match($pattern[0], $buffer, $arr)) {
                $lines .= $arr[2].";http://bdtd.ibict.br".$arr[1].";".$pag."\n";
            }

            //<a href="/vufind/Search/Results?lookfor=cotas&amp;type=AllFields&amp;page=165">[165]</a>
            //<a href="/vufind/Search/Results?join=AND&amp;bool0%5B%5D=AND&amp;lookfor0%5B%5D=raci+OR+ra%C3%A7+OR+negr+OR+pard+OR+pret&amp;type0%5B%5D=AllFields&amp;page=2">
            //$pattern[1] = "/<a href=\"\/vufind\/Search\/Results\?lookfor=(.*)&amp;type=AllFields&amp;page=([0-9]*)\">\[[0-9]*\]<\/a>/";
            //$pattern[1] = "/=AllFields&amp;page=([0-9]*)\">\[[0-9]*\]<\/a>/";
            //[1564]</a>
            $pattern[1] = "/\[([0-9]*)\]<\/a>/";
            if (preg_match($pattern[1], $buffer, $arr)) {
                //$pg_total = $arr[2];
                $pg_total = $arr[1];
                if ($output == "") {
                    //$aux = retirarAcento(urldecode($arr[1]));
                    @mkdir("./output/".$aux);
                    @mkdir("./output/".$aux."/paginas");
                    
                    $output = "./output/".$aux."/".date("Y-m-d")."-".$aux.".csv";
                }
            }
            $output2 = "./output/".$aux."/paginas/raca-".$pag.".html";
        }        
    }
    
    handlefiles($output, $lines);
    handlefiles($output2, $html);
    
    echo "File: ".$output2."\n";
    sleep(3);

    if ($pag < $pg_total) {
        getlinks($url, $output, ++$pag);
    }
    return true;
}

function getlinkcontents($file, $pag = 1) {
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if ($data[2] >= $pag) {
                $aux_file_name = preg_split( "/(\/|-|\.)/", $file);
                $output_file_name = $aux_file_name[sizeof($aux_file_name)-2]."-".$data[2]."-".$data[0].".html";
                
                $aux_path = explode('/', $file);
                array_pop($aux_path);
                $path = implode('/', $aux_path)."/";

                $output_path = $path."conteudo";
                @mkdir($output_path);
                
                $output = $output_path."/".$output_file_name;
                
                $contents = file_get_contents(trim($data[1]));
                handlefiles($output, $contents);

                echo "File: ".$output."\n";
                sleep(3);        
            }
        }
    } else {
        return false;
    }
    return true;
}

//getlinks("http://bdtd.ibict.br/vufind/Search/Results?join=AND&lookfor0%5B%5D=raci+OR+ra%C3%A7+OR+negr+OR+pard+OR+pret&type0%5B%5D=AllFields&bool0%5B%5D=AND&illustration=-1&daterange%5B%5D=publishDate&publishDatefrom=&publishDateto=", $file, 1020);

$file = "./output/raca/2020-04-14-raca.csv";
getdata($file);

?>


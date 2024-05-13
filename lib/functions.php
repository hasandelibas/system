<?php



function slugify($text) {
  $find    = array('Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ş', 'ğ', 'ü', 'ö', 'ı', '+', '#');
  $replace = array('c', 's', 'g', 'u', 'i', 'o', 'c', 's', 's', 'g', 'u', 'o', 'i', 'plus', 'sharp' );
  $text = strtolower(str_replace($find, $replace, $text));
  $text = preg_replace("@[^A-Za-z0-9\-_\.\+]@i", ' ', $text);
  $text = trim(preg_replace('/\s+/', ' ', $text));
  $text = str_replace(' ', '-', $text);
  $text = str_replace('.', '', $text);

  return $text;
}



function write(...$arg){
  /*$WRITE = true;  
  if($WRITE) ob_start();*/
  if(count($arg)==1 AND is_string($arg[0]) == false){
    print_r($arg[0]);
  }else{
    echo join(" ",$arg) . "\n";
  }
  /*if($WRITE){
    $OUTPUT = ob_get_contents();
    ob_end_clean();
    file_put_contents("write.log",$OUTPUT,FILE_APPEND);
  }*/
}



function csv_parse($code, $delimiter = ',', $lineBreak = "\n") {
  if(trim($code)=="") return [];
  $csvArray = [];
  $rows = str_getcsv($code, $lineBreak);
  foreach ($rows as $row) {
      $csvArray[] = str_getcsv($row, $delimiter);
  }
  return $csvArray;
}

function csv_stringify($arr){
  $code = "";
  $rows = [];
  if(count($arr)==0) return "";
  if( is_numeric(array_keys($arr[0])[0]) ) {
    foreach($arr as $col){
      $line = [];
      foreach($col as $data){
          $line[] = join('""', explode('"',$data) );
      }
      $rows[] =  '"' . join('","',$line) . '"';
    }
    return join("\n",$rows);
  }else{
    $headers = array_keys($arr[0]);
    $rows [] = join(",",$headers);
    foreach($arr as $col){
      $line = [];
      foreach($col as $data){
        $line[] = join('""', explode('"',$data) );
      }
      $rows[] =  '"' . join('","',$line) . '"';
    }
    return join("\n",$rows);
  }
}

class CSV{
  public static function parse($code, $delimiter = ',', $lineBreak = "\n"){
    return csv_parse($code,$delimiter,$lineBreak);
  }
  public static function stringify($arr){
    return csv_stringify($arr);
  }
}


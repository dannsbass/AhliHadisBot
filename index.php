<?php init();

// jika user mengirim /start
//$bot->cmd('/start','Untuk mencari hadis, kirim /cari [kalimat yang dicari]. Contoh: /cari puasa ramadhan');

// jika user mengirim /cari kalimat tertentu
$bot->regex('/^\/?cari\s?:?(.*)/i',function($cocok){

  if(!diizinkan())return;

  //ambil kalimat yang dicari
  $q = urlencode(trim($cocok[1]));
  $find = array("َ","ِ","ُ","ً","ٍ","ٌ","ْ","ّ");
  $q = str_replace($find,"",$q);

  // jika tidak ada kalimat yang dicari
  if(empty($q))return Bot::sendMessage("/cari [kata yang dicari]\ncontoh: /cari puasa ramadhan",reply());

  // jika ada kalimat yang dicari
  typing();
  $pesan = Bot::sendMessage('Mohon tunggu sebentar, kami sedang mencari...',reply());

  $pesan = json_decode($pesan,true);
  $message_id = $pesan['result']['message_id'];

  // ambil dari carihadis.com
  $json = file_get_contents('http://api.carihadis.com/?q='.urlencode($q));

  // olah hasil menjadi array
  $isi = json_decode($json,true);

  //jika hasilnya kosong
  typing();
  if($isi['data'] == null) {
    return Bot::editMessageText(['message_id'=>$message_id,'text'=>'Maaf, kami tidak berhasil menemukan. Silahkan coba kalimat lain'],reply());  
  }

  // jika hasil tidak kosong, siapkan hasil yang mau dikirim  
  $hasil = '';

  // olah hasil menjadi pesan
  foreach($isi as $data){
	  foreach($data as $i=>$a){
		  $ar = $a['id'];
		  foreach($ar as $id){
			  $hasil .= '/'.$a['kitab'].$id."\n";
		  }
		  
	  }
  }

  $hasil = "Ditemukan hasil sebagai berikut:\n\n$hasil";

  if(strlen($hasil) > 4096 ){
    return Bot::editMessageText(['message_id'=>$message_id,'text'=>"Hasil terlalu banyak, silahkan cari kata atau kalimat yang lebih spesifik, atau lihat versi <a href='https://carihadis.com/?teks=$q'>web</a>.",'reply'=>true,'parse_mode'=>'html']);
  }

  // kirim pesan balasan ke user  
  typing();
  return Bot::editMessageText(['message_id'=>$message_id,'text'=>$hasil,'reply'=>true]);

});

// jika user mengirim /Nama_Kitab123
$bot->regex('/^\/([a-zA-Z_]+)(\d+)(\@.+bot)?$/i',function($cocok){
  if(!diizinkan())return;
  
	$kitab = cukur($cocok[1]);
	$id = $cocok[2];
	
	$hasil = file_get_contents("http://api.carihadis.com/?kitab=$kitab&id=$id");
	
	$hasil = json_decode($hasil,true);

  // jika nama kitab salah atau nomor hadis tidak ada
  if(empty($hasil['data'])) return Bot::sendMessage('Link tidak valid',reply());
  
  $ref = str_replace('_',' ',$kitab);
  $ref = "<a href='https://carihadis.com/$kitab/$id'>$ref $id</a>";
  
	$nass = strip_tags($hasil['data'][1]['nass']);
	$terjemah = strip_tags($hasil['data'][1]['terjemah']);
  $terjemah = str_replace(']','</b>',$terjemah);
  $terjemah = str_replace('[','<b>',$terjemah);
  $pesan = "$ref\n\n$nass\n\n$terjemah";
  
  if(strlen($pesan) > 4096 ){
    
    $pesan = potong($pesan,4096);
        
    $keyboard[] = [
        ['text' => 'Lihat selengkapnya', 'url' => "https://carihadis.com/$kitab/$id"],
    ];

    $options = [
        'reply_markup' => ['inline_keyboard' => $keyboard],
        'reply'=>true,
        'parse_mode'=>'html'
    ];

    return Bot::sendMessage($pesan[0],$options);
  }

  typing();	
	return Bot::sendMessage($pesan,['reply'=>true,'parse_mode'=>'html']);
});

// jika user mengirim teks bebas
//$bot->cmd('*','/cari [kalimat yang ingin dicari]');

// jika user mengirim apa saja 
//$bot->on('*','/cari [kalimat yang ingin dicari]');

// jalankan bot
$bot->run();

// kumpulan function
function reply(){
  return ['reply'=>true];
}

function typing(){
  return Bot::sendChatAction('',['action'=>'typing']);
}
function cukur($a){
  if(strrpos($a,'_') == strlen($a) - 1 ) { 
    $a = substr($a,0,-1);
    $a = cukur($a);
  }
  if(strpos($a,'_') === 0){
    $a = substr($a,1);
    $a = cukur($a);
  }
  return $a;
}

    function pecah($text,$jml_kar){
        $karakter = $text{$jml_kar};
        while($karakter != ' ' AND $karakter != "\n" AND $karakter != "\r" AND $karakter != "\r\n") {//kalau bukan spasi atau new line
            $karakter = $text[--$jml_kar];//cari spasi sebelumnya
        }
        $pecahan = substr($text, 0, $jml_kar);
        return trim($pecahan);
    }

    function potong($text,$jml_kar){
        $panjang = strlen($text);
        $ke = 0;
        $pecahan = [];
        while($panjang>$jml_kar){
            $pecahan[] = pecah($text,$jml_kar);//str
            $panjang = strlen($pecahan[$ke]);//int
            $text = trim(substr($text,$panjang));//str
            $panjang = strlen($text);//int
            $ke++;//int
        }
        $array = array_merge($pecahan, array($text));
        return $array;
    }

function init(){
  require __DIR__.'/PHPTelebot.php';
  $GLOBALS['bot'] = new PHPTelebot("\x32\x31\x32\x36\x31\x34\x31\x31\x32\x37\x3A\x41\x41\x45\x6A\x57\x42\x52\x78\x59\x33\x6C\x63\x66\x35\x64\x34\x43\x51\x47\x64\x34\x56\x61\x52\x2D\x58\x6B\x63\x35\x74\x55\x4D\x63\x59\x51","\x41\x68\x6C\x69\x48\x61\x64\x69\x73\x42\x6F\x74");
}

function diizinkan(){
  $msg = Bot::message();
  $id = $msg['chat']['id'];
  $allowed = [-1001198104691,685631733,1231968913];
  if(!in_array($id,$allowed)) return  false; #Bot::sendMessage("Maaf, bot ini hanya bisa diakses melalui <a href='https://t.me/Dannsbass'>Grup Cari Hadis</a>",['parse_mode'=>'html']);
  return true;
}
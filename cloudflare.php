<?php
/**
 * Classe para tratar o cache no CloudFlare
 * 
 */
class CloudFlare extends Basic {


	const API_KEY = 'xxxxxxxxxxxxxxxx';
	const EMAIL = 'xxxxxxxx@gmail.com';
	
	public function __construct(){

		ini_set('memory_limit','512M');
		ini_set('max_execution_time', 60);


	}

	/**
	 * requisita as zonas do cloudflare onde o dns da conta está ativo
	 */
	public function getZones($page = 1, $per_page = 100) {

		$URL = 'https://api.cloudflare.com/client/v4/zones?status=active&page='.$page.'&per_page='.$per_page.'&order=status&direction=desc&match=all';
		$header = array();
		$header[] = 'X-Auth-Email:'.self::EMAIL;
		$header[] = "X-Auth-Key:".self::API_KEY;
		$header[] = "Content-Type: application/json";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$URL);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		//curl_setopt($ch, CURLOPT_REFERER, '');
		$result=curl_exec ($ch);
		curl_close ($ch);

		return json_decode($result);

	}

	public function removeCache(Array $URLs){
						
		if(count($URLs)<=0){
			throw new Exception("Nenhuma URL foi informada");
		}

		$data = $this->getZones();
		if (!$data) {
			//throw new Exception("Zona não foi requisitada");
			return;
		}
				
		if ($data->success!=true) {
			throw new Exception("Falha: " . (isset($data->errors) ? $data->errors : '[0]') . ( isset($data->messages) ? $data->messages : 'Erro inesperado'));
		}
		$result = isset($data->result) ? $data->result : false;
		if ($result==false) {
			throw new Exception("Requisição incompleta");
		}

		$zones = array();
		$idZone = '';
		///pega o ID da zona pela primeira requisiçao 
		foreach ($URLs as $url) {
			///******
			$iUrl = parse_url($url);
			$domain = str_replace('www.','',$iUrl['host']);
			
			//verifica se o ID da zona ja esta no array
			if (array_key_exists($domain, $zones)) {
				$idZone = $zones[$domain];
			}else{
				foreach ($result as $tmp){
					if ($tmp->name == $domain) {
						$idZone = $tmp->id;
						$zones[$domain] = $idZone;
					}
				}
			}
			//******
		} 
		
		if ($idZone == '') {
			throw new Exception("$domain não encontrado");
		}
		
    
		$idZone = $result[0]->id;
	
    //Envia a requisição para limpar o cache
		$a = array('files'=>$URLs);
		
		$fields_string  = json_encode($a);

		$URL = 'https://api.cloudflare.com/client/v4/zones/'.$idZone.'/purge_cache';
		$header = array();
		$header[] = 'X-Auth-Email:'.self::EMAIL;
		$header[] = "X-Auth-Key:".self::API_KEY;
		$header[] = "Content-Type: application/json"; 
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$URL);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

		curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "DELETE"); 
		curl_setopt($ch,CURLOPT_POSTFIELDS, ''.$fields_string);

		$result=curl_exec ($ch);
		curl_close ($ch);
	}
}
?>

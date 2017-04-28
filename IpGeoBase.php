<?php
/**
 * @author Самсонов Владимир <samsonov.sem@gmail.com>
 * @copyright Copyright &copy; S.E.M. 2017-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace sem\ipgeobase;

use Yii;
use yii\base\Component;

/**
 * Компонент реализует логику взаимодействиея с сервисом @link http://ipgeobase.ru/Help.html
 * И работает как с локальной базой сервиса, которая находится в свободном доспуте, так и с сервисом напрямую
 * 
 * @property integer $serviceTimeout
 */
class IpGeoBase extends Component
{

    /**
     * Количество секунд для таймаута при работе с сервисом напрямую
     * @var integer
     */
    public $serviceTimeout = 5;
    
    /**
     * Основной поисковый SQL-запроса
     * @var \yii\db\Query
     */
    protected $_query;


    /**
     * Возвращает IP-адрес клиентского запроса
     * 
     * @return string|null
     */
    public function getUserIp()
    {
        $userIp = null;
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            
            $userIp = $_SERVER['HTTP_CLIENT_IP'];
            
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            
            $userIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
            
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            
            $userIp = $_SERVER['REMOTE_ADDR'];
            
        }
        
        return $userIp;
    }
    
    /**
     * Возвращает геолокационные данные по IP-адресу.
     * Если Ip-адрес не указан,
     * то будет произведена попытка определения текущего IP-адреса пользователя
     * @param string $ip IP-адрес, для котрого определяется GEO-информация
     * @return \stdClass|null
     */
    public function getGeo($ip = null)
    {
	$geo = null;
	
	if (is_null($ip)) {
	    $ip = $this->getUserIp();
	}
	
	if ($ip) {
	    
	    if (!$geo = $this->byDb($ip)) {
		$geo = $this->byCUrl($ip);
	    }
	    
	}
	
	return $geo;
    }
    
    /**
     * Возвращает Гео-информацию по названию города
     * @param string $city
     * @return \stdClass|null
     */
    public function getCityInfo($city)
    {
	try {

	    $query = $this->getQuery();
	    $query->where('[[c]].[[city]] = :city', [
		':city'	=>  $city
	    ]);
	    
	    $info = null;
	    
	    if ($result = $query->one()) {
		$info = (object) [
		    'country'   =>  $result['country'],
		    'city'      =>  $result['city'],
		    'region'    =>  $result['region'],
		    'district'  =>  $result['district'],
		    'lat'       =>  (float) $result['lat'],
		    'lng'       =>  (float) $result['lng'],
		];
	    }
	    
	} catch (\Exception $exc) {
	    
	    $info = null;
	    
	}
	
	return $info;
    }
    
    /**
     * Определение геолокации путем запроса из локальной БД
     * @see http://faniska.ru/php-kusochki/import-bazy-ipgeobase-v-lokalnuyu-bazu-dannyx-i-dalnejshee-ispolzovanie.html
     * @param string $ip IP-адрес, для котрого определяется GEO-информация
     * @return \stdClass|null
     */
    protected function byDb($ip)
    {
	
	try {
	    
	    $query = $this->getQuery();
	    $query->where('[[b]].[[long_ip1]] <= :longIp AND [[b]].[[long_ip2]] >= :longIp', [
		':longIp'	=>  ip2long($ip)
	    ]);
	    
	    $geo = null;
	    
	    if ($result = $query->one()) {
		
		$geo = (object) [
		    'country'   =>  $result['country'],
		    'city'      =>  $result['city'],
		    'region'    =>  $result['region'],
		    'district'  =>  $result['district'],
		    'lat'       =>  (float) $result['lat'],
		    'lng'       =>  (float) $result['lng'],
		];
		
	    }
	    
	} catch (\Exception $exc) {
	   $geo = null; 
	}
	
	return $geo;
    }
    
    /**
     * Определение геолокации путем непосредствееного взаимдействия с сервисом
     * @param string $ip IP-адрес, для котрого определяется GEO-информация
     * @return \stdClass|null
     */
    protected function byCUrl($ip)
    {
	$serviceUrl = 'http://ipgeobase.ru:7020/geo?ip=' . urlencode($ip);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $serviceUrl);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, $this->serviceTimeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->serviceTimeout);

	if ($xmlData = curl_exec($ch)) {
	    
	    $xml = new \SimpleXMLElement($xmlData);
	    if ($xml->ip && !isset($xml->ip->message)) {
		
		return (object) [
		    'country'   =>  (string)$xml->ip->country,
		    'city'      =>  (string)$xml->ip->city,
		    'region'    =>  (string)$xml->ip->region,
		    'district'  =>  (string)$xml->ip->district,
		    'lat'       =>  (float)$xml->ip->lat,
		    'lng'       =>  (float)$xml->ip->lng,
		];
		
	    }
	    
	}
	
	return null;
    }
    
    /**
     * Подготавливает и возвращает основной запрос
     * @return \yii\db\Query
     */
    protected function getQuery()
    {
	
	if (is_null($this->_query)) {
	    $this->_query = (new \yii\db\Query)->select([
		'[[b]].[[country]]',
		'[[c]].[[city]]',
		'[[c]].[[region]]',
		'[[c]].[[district]]',
		'[[c]].[[lat]]',
		'[[c]].[[lng]]'
	    ])->
	    from('{{%geo__base}} [[b]]')->
	    leftJoin([
		'{{%geo__cities}} [[c]]'
	    ], '[[b]].[[city_id]] = [[c]].[[city_id]]')->
	    limit(1);
	}
	
	return $this->_query;
    }
    
}

<?php

require(realpath(dirname(__FILE__) . '/../includes/configs.php'));

function config($data)
{
    global $configs;
    $serverInf = [
        'pw-api' => [
            'local' => $configs['api']['host'],
            'ports' => ['gamedbd' => $configs['api']['gamedbd'], 'gdeliveryd' => $configs['api']['gdeliveryd'], 'gacd' => $configs['api']['gacd'], 'client' => $configs['api']['client']],
            'game_version' => $configs['api']['version'],
            'maxbuffer' => 65536,
            's_block' => false,
            's_readtype' => 3,
        ],
    ];
    eval('$r = $serverInf[\'' . str_replace('.', '\'][\'', $data) . '\'];');
    return $r;
}

function setting($key, $valueIfNull = null)
{
    if ($key == 'server_version') {
        $ret = config('pw-api.game_version');
    } elseif ($key == 'server_ip') {
        $ret = config('pw-api.local');
    } else {
        $ret = '';
    }
    return $ret != '' ? $ret : $valueIfNull;
}

class Gamed
{
    public $ip;
    public $version;
    public $cycle = false;
    public function __construct()
    {
        $this->cycle = false;
    }
    public function deleteHeader($data)
    {
        $length = 0;
        $this->unpackCuint($data, $length);
        $this->unpackCuint($data, $length);
        $length += 8;
        $data = substr($data, $length);
        return $data;
    }
    public function createHeader($opcode, $data)
    {
        return $this->cuint($opcode) . $this->cuint(strlen($data)) . $data;
    }
    public function packString($data)
    {
        $data = iconv("UTF-8", "UTF-16LE//TRANSLIT", utf8_encode($data));
        return $this->cuint(strlen($data)) . $data;
    }
    public function packLongOctet($data)
    {
        return pack("n", strlen($data) + 32768) . $data;
    }
    public function packOctet($data)
    {
        $data = pack("H*", (string) $data);
        return $this->cuint(strlen($data)) . $data;
    }
    public function packInt($data)
    {
        return pack("N", $data);
    }
    public function packByte($data)
    {
        return pack("C", $data);
    }
    public function packFloat($data)
    {
        return strrev(pack("f", $data));
    }
    public function packShort($data)
    {
        return pack("n", $data);
    }
    public function packLong($data)
    {
        $left = 0xffffffff00000000;
        $right = 0x00000000ffffffff;
        $l = ($data & $left) >> 32;
        $r = $data & $right;
        return pack('NN', $l, $r);
    }
    public function hex2octet($tmp)
    {
        $t = 8 - strlen($tmp);
        for ($i = 0; $i < $t; $i++) {
            $tmp = '0' . $tmp;
        }
        return $tmp;
    }
    public function reverseOctet($str)
    {
        $octet = '';
        $length = strlen($str) / 2;
        for ($i = 0; $i < $length; $i++) {
            $tmp = substr($str, -2);
            $octet .= $tmp;
            $str = substr($str, 0, -2);
        }
        return $octet;
    }
    public function hex2int($value)
    {
        $value = str_split($value, 2);
        $value = $value[3] . $value[2] . $value[1] . $value[0];
        $value = hexdec($value);
        return $value;
    }
    public function getTime($str)
    {
        return hexdec($str);
    }
    public function getIp($str)
    {
        return long2ip(hexdec($str));
    }
    public function putIp($str)
    {
        $ip = ip2long($str);
        $ip = dechex($ip);
        $ip = hexdec($this->reverseOctet($ip));
        return $ip;
    }
    public function cuint($data)
    {
        if ($data < 64) {
            return strrev(pack("C", $data));
        } elseif ($data < 16384) {
            return strrev(pack("S", $data | 0x8000));
        } elseif ($data < 536870912) {
            return strrev(pack("I", $data | 0xc0000000));
        }
        return strrev(pack("c", -32) . pack("i", $data));
    }
    public function unpackLong($data)
    {
        //$data = pack("H*", $data);
        $set = unpack('N2', $data);
        return ($set[1] << 32) | $set[2];
    }
    public function unpackOctet($data, &$tmp)
    {
        $p = 0;
        $size = $this->unpackCuint($data, $p);
        $octet = bin2hex(substr($data, $p, $size));
        $tmp = $tmp + $p + $size;
        return $octet;
    }
    public function unpackString($data, &$tmp)
    {
        $size = hexdec(bin2hex(substr($data, $tmp, 1))) >= 128 ? 2 : 1;
        $octetlen = hexdec(bin2hex(substr($data, $tmp, $size))) >= 128 ? hexdec(bin2hex(substr($data, $tmp, $size))) - 32768 : hexdec(bin2hex(substr($data, $tmp, $size)));
        $pp = $tmp;
        $tmp += $size + $octetlen;
        return mb_convert_encoding(substr($data, $pp + $size, $octetlen), "UTF-8", "UTF-16LE");
    }
    public function unpackCuint($data, &$p)
    {
        if (setting('server_version', '101') != '07') {
            $hex = hexdec(bin2hex(substr($data, $p, 1)));
            $min = 0;
            if ($hex < 0x80) {
                $size = 1;
            } elseif ($hex < 0xc0) {
                $size = 2;
                $min = 0x8000;
            } elseif ($hex < 0xe0) {
                $size = 4;
                $min = 0xc0000000;
            } else {
                $p++;
                $size = 4;
            }
            $data = hexdec(bin2hex(substr($data, $p, $size)));
            $unpackCuint = $data - $min;
            $p += $size;
            return $unpackCuint;
        } else {
            $byte = unpack("Carray", substr($data, $p, 1));
            if ($byte['array'] < 0x80) {
                $p++;
            } elseif ($byte['array'] < 0xc0) {
                $byte = unpack("Sarray", strrev(substr($data, $p, 2)));
                $byte['array'] -= 0x8000;
                $p += 2;
            } elseif ($byte['array'] < 0xe0) {
                $byte = unpack("Iarray", strrev(substr($data, $p, 4)));
                $byte['array'] -= 0xc0000000;
                $p += 4;
            } else {
                $prom = strrev(substr($data, $p, 5));
                $byte = unpack("Iarray", strrev($prom));
                $p += 4;
            }
            return $byte['array'];
        }
    }
    public function SendToGamedBD($data)
    {
        return $this->SendToSocket($data, config('pw-api.ports.gamedbd'));
    }
    public function SendToDelivery($data)
    {
        return $this->SendToSocket($data, config('pw-api.ports.gdeliveryd'), true);
    }
    public function SendToProvider($data)
    {
        return $this->SendToSocket($data, config('pw-api.ports.gacd'));
    }
    public function SendToSocket($data, $port, $RecvAfterSend = false, $buf = null)
    {
        if (@fsockopen(setting('server_ip', '127.0.0.1'), $port, $errCode, $errStr, 1)) {
            $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($sock, setting('server_ip', '127.0.0.1'), $port);
            if (config('pw-api.s_block')) {
                socket_set_block($sock);
            }
            if ($RecvAfterSend) {
                socket_recv($sock, $tmp, 8192, 0);
            }
            socket_send($sock, $data, strlen($data), 0);
            switch (config('pw-api.s_readtype')) {
                case 1:
                    socket_recv($sock, $buf, config('pw-api.maxbuffer'), 0);
                    break;
                case 2:
                    $buffer = socket_read($sock, 1024, PHP_BINARY_READ);
                    while (strlen($buffer) == 1024) {
                        $buf .= $buffer;
                        $buffer = socket_read($sock, 1024, PHP_BINARY_READ);
                    }
                    $buf .= $buffer;
                    break;
                case 3:
                    $tmp = 0;
                    $buf .= socket_read($sock, 1024, PHP_BINARY_READ);
                    if (strlen($buf) >= 8) {
                        $this->unpackCuint($buf, $tmp);
                        $length = $this->unpackCuint($buf, $tmp);
                        while (strlen($buf) < $length) {
                            $buf .= socket_read($sock, 1024, PHP_BINARY_READ);
                        }
                    }
                    break;
            }
            if (config('pw-api.s_block')) {
                socket_set_nonblock($sock);
            }
            socket_close($sock);
            return $buf;
        } else {
            return false;
        }
    }
    public function unmarshal(&$rb, $struct)
    {
        $data = [];
        foreach ($struct as $key => $val) {
            if (is_array($val)) {
                if ($this->cycle) {
                    if ($this->cycle > 0) {
                        for ($i = 0; $i < $this->cycle; $i++) {
                            $data[$key][$i] = $this->unmarshal($rb, $val);
                            if (!$data[$key][$i]) {
                                return false;
                            }
                        }
                    }
                    $this->cycle = false;
                } else {
                    $data[$key] = $this->unmarshal($rb, $val);
                    if (!$data[$key]) {
                        return false;
                    }
                }
            } else {
                $tmp = 0;
                switch ($val) {
                    case 'int':
                        $un = unpack("N", substr($rb, 0, 4));
                        $rb = substr($rb, 4);
                        $data[$key] = $un[1];
                        break;
                    case 'int64':
                        $un = unpack("N", substr($rb, 0, 8));
                        $rb = substr($rb, 8);
                        $data[$key] = $un[1];
                        break;
                    case 'long':
                        $data[$key] = $this->unpackLong(substr($rb, 0, 8));
                        $rb = substr($rb, 8);
                        break;
                    case 'lint':
                        //$un = unpack("L", substr($rb,0,4));
                        $un = unpack("V", substr($rb, 0, 4));
                        $rb = substr($rb, 4);
                        $data[$key] = $un[1];
                        break;
                    case 'byte':
                        $un = unpack("C", substr($rb, 0, 1));
                        $rb = substr($rb, 1);
                        $data[$key] = $un[1];
                        break;
                    case 'cuint':
                        $cui = $this->unpackCuint($rb, $tmp);
                        $rb = substr($rb, $tmp);
                        if ($cui > 0) {
                            $this->cycle = $cui;
                        } else {
                            $this->cycle = -1;
                        }
                        break;
                    case 'octets':
                        $data[$key] = $this->unpackOctet($rb, $tmp);
                        $rb = substr($rb, $tmp);
                        break;
                    case 'name':
                        $data[$key] = $this->unpackString($rb, $tmp);
                        $rb = substr($rb, $tmp);
                        break;
                    case 'short':
                        $un = unpack("n", substr($rb, 0, 2));
                        $rb = substr($rb, 2);
                        $data[$key] = $un[1];
                        break;
                    case 'lshort':
                        $un = unpack("v", substr($rb, 0, 2));
                        $rb = substr($rb, 2);
                        $data[$key] = $un[1];
                        break;
                    case 'float2':
                        $un = unpack("f", substr($rb, 0, 4));
                        $rb = substr($rb, 4);
                        $data[$key] = $un[1];
                        break;
                    case 'float':
                        $un = unpack("f", strrev(substr($rb, 0, 4)));
                        $rb = substr($rb, 4);
                        $data[$key] = $un[1];
                        break;
                }
                if ($val != 'cuint' and is_null($data[$key])) {
                    return false;
                }
            }
        }
        return $data;
    }
    public function marshal($pack, $struct)
    {
        $this->cycle = false;
        $data = '';
        foreach ($struct as $key => $val) {
            if (substr($key, 0, 1) == "@") {
                continue;
            }
            if (is_array($val)) {
                if ($this->cycle) {
                    if ($this->cycle > 0) {
                        $count = $this->cycle;
                        for ($i = 0; $i < $count; $i++) {
                            $data .= $this->marshal($pack[$key][$i], $val);
                        }
                    }
                    $this->cycle = false;
                } else {
                    $data .= $this->marshal($pack[$key], $val);
                }
            } else {
                switch ($val) {
                    case 'int':
                        $data .= $this->packInt((int) $pack[$key]);
                        break;
                    case 'byte':
                        $data .= $this->packByte($pack[$key]);
                        break;
                    case 'cuint':
                        $arrkey = substr($key, 0, -5);
                        $cui = isset($pack[$arrkey]) ? count($pack[$arrkey]) : 0;
                        $this->cycle = $cui > 0 ? $cui : -1;
                        $data .= $this->cuint($cui);
                        break;
                    case 'octets':
                        if ($pack[$key] === []) {
                            $pack[$key] = '';
                        }
                        $data .= $this->packOctet($pack[$key]);
                        break;
                    case 'name':
                        if ($pack[$key] === []) {
                            $pack[$key] = '';
                        }
                        $data .= $this->packString($pack[$key]);
                        break;
                    case 'short':
                        $data .= $this->packShort($pack[$key]);
                        break;
                    case 'float':
                        $data .= $this->packFloat($pack[$key]);
                        break;
                    case 'cat1':
                    case 'cat2':
                    case 'cat4':
                        $data .= $pack[$key];
                        break;
                }
            }
        }
        return $data;
    }
    public function MaxOnlineUserID($arr)
    {
        $max = $arr[0]['userid'];
        for ($i = 1; $i < count($arr); $i++) {
            if ($arr[$i]['userid'] > $max) {
                $max = $arr[$i]['userid'];
            }
        }
        return $max + 1;
    }
    public function getArrayValue($array = [], $index = null)
    {
        return $array[$index];
    }
}
class API
{
    public $online;
    public $data = [];
    public $gamed;
    public function __construct()
    {
        // Set some default values
        $this->gamed = new Gamed();
        $this->online = $this->serverOnline();
        // Check if there is a protocol file for the set game version
        $version = setting('server_version', '101');
        if (file_exists(__DIR__ . '/pw_v' . $version . '.php')) {
            require __DIR__ . '/pw_v' . $version . '.php';
            if (isset($pwapi)) {
                $this->data = $pwapi;
            }
        } else {
            throw new Exception('No config file was found to the given version: ' . $version . '');
        }
    }
    /**
     * Returns the array of role data by structure
     * @params string $role
     * @return array
     */
    public function getRole($role)
    {
        if (setting('server_version') == '07') {
            $user['base'] = $this->getRoleBase($role);
            $user['status'] = $this->getRoleStatus($role);
            $user['pocket'] = $this->getRoleInventory($role);
            //$user['pets'] = $this->getRolePetBadge($role);
            $user['equipment'] = $this->getRoleEquipment($role);
            $user['storehouse'] = $this->getRoleStoreHouse($role);
            $user['task'] = $this->getRoleTask($role);
        } else {
            $pack = pack("N*", -1, $role);
            $pack = $this->gamed->createHeader($this->data['code']['getRole'], $pack);
            $send = $this->gamed->SendToGamedBD($pack);
            $data = $this->gamed->deleteHeader($send);
            $user = $this->gamed->unmarshal($data, $this->data['role']);
            if (!is_array($user)) {
                $user['base'] = $this->getRoleBase($role);
                $user['status'] = $this->getRoleStatus($role);
                $user['pocket'] = $this->getRoleInventory($role);
                //$user['pets'] = $this->getRolePetBadge($role);
                $user['equipment'] = $this->getRoleEquipment($role);
                $user['storehouse'] = $this->getRoleStoreHouse($role);
                $user['task'] = $this->getRoleTask($role);
            }
        }
        return $user;
    }
    public function getRoleBase($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleBase'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['base']);
        return $user;
    }
    public function getRoleStatus($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleStatus'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['status']);
        return $user;
    }
    public function getRoleInventory($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleInventory'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['pocket']);
        return $user;
    }
    public function getRoleEquipment($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleEquipment'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['equipment']);
        return $user;
    }
    public function getRolePetBadge($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader(3088, $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['pocket']['petbadge']);
        return $user;
    }
    public function getRoleStorehouse($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleStoreHouse'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $store = $this->gamed->unmarshal($data, $this->data['role']['storehouse']);
        return $store;
    }
    public function getRoleTask($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleTask'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['task']);
        return $user;
    }
    /**
     * Returns the array of role data by structure
     * @params string $role
     * @return array
     */
    public function getJdRole($role)
    {
        /*$pack = pack("N*", -1, $role);
$pack = $this->gamed->createHeader(config('code.getRole'), $pack);
$send = $this->gamed->SendToGamedBD($pack);
$data = $this->gamed->deleteHeader($send);
$user = $this->gamed->unmarshal($data, config('role'));

return $user;*/
    }
    /**
     * Returns the array of user roles by structure
     * @params string $user
     * @return array
     */
    public function getRoles($user)
    {
        $pack = pack("N*", -1, $user);
        $pack = $this->gamed->createHeader($this->data['code']['getUserRoles'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $roles = $this->gamed->unmarshal($data, $this->data['user']['roles']);
        return $roles;
    }
    /**
     * Returns the array of user data by structure
     * @params int $id
     * @return array
     */
    public function getUser($id)
    {
        $pack = pack("N*", -1, $id, 1, 1);
        $data = $this->gamed->cuint($this->data['code']['getUser']) . $this->gamed->cuint(strlen($pack)) . $pack;
        $send = $this->gamed->SendToGamedBD($data);
        $strlarge = unpack("H", substr($send, 2, 1));
        if (substr($strlarge[1], 0, 1) == 8) {
            $tmp = 12;
        } else {
            $tmp = 11;
        }
        $send = substr($send, $tmp);
        $user = $this->gamed->unmarshal($send, $this->data['user']['info']);
        $user['login_ip'] = $this->gamed->getIp($this->gamed->reverseOctet(substr($user['login_record'], 8, 8)));
        $user['login_time'] = $this->gamed->getTime(substr($user['login_record'], 0, 8));
        return $user;
    }
    /**
     * Returns the array of user data by structure
     * @params string $user
     * @return array
     */
    public function getJdUser($id)
    {
        /*$pack = pack("N*", -1, $id);
$data = $this->gamed->SendToGamedBD($this->gamed->createHeader(config('code.getUser'), $pack));
$send = $this->gamed->SendToGamedBD($data);
return $this->gamed->unmarshal($data, config('user.info'));*/
    }
    /**
     * Saves a data of character by structure
     * @params string $role
     * @params array $params
     * @return boolean
     */
    public function putRole($role, $params)
    {
        if (isset($params['equipment']['eqp']['id'])) {
            $tmp = $params['equipment']['eqp'];
            $params['equipment']['eqp'] = [];
            $params['equipment']['eqp'][] = $tmp;
        }
        if (isset($params['pocket']['inv']['id'])) {
            $tmp = $params['pocket']['inv'];
            $params['pocket']['inv'] = [];
            $params['pocket']['inv'][] = $tmp;
        }
        if (isset($params['storehouse']['store']['id'])) {
            $tmp = $params['storehouse']['store'];
            $params['storehouse']['store'] = [];
            $params['storehouse']['store'][] = $tmp;
        }
        if (isset($params['task']['task_inventory']['id'])) {
            $tmp = $params['task']['task_inventory'];
            $params['task']['task_inventory'] = [];
            $params['task']['task_inventory'][] = $tmp;
        }
        if (isset($params['storehouse']['dress']['id'])) {
            $tmp = $params['storehouse']['dress'];
            $params['storehouse']['dress'] = [];
            $params['storehouse']['dress'][] = $tmp;
        }
        if (isset($params['storehouse']['material']['id'])) {
            $tmp = $params['storehouse']['material'];
            $params['storehouse']['material'] = [];
            $params['storehouse']['material'][] = $tmp;
        }
        if (setting('server_version') != '07') {
            $pack = pack("NNC*", -1, $role, 1) . $this->gamed->marshal($params, $this->data['role']);
            return $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['putRole'], $pack));
        } else {
            $pack = pack("NNC*", -1, $role) . $this->gamed->marshal($params["base"], $this->data['role']['base']);
            $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['putRoleBase'], $pack));
            $pack = pack("NNC*", -1, $role) . $this->gamed->marshal($params["status"], $this->data['role']['status']);
            $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['putRoleStatus'], $pack));
            $pack = pack("NNC*", -1, $role) . $this->gamed->marshal($params["pocket"], $this->data['role']['pocket']);
            $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['putRoleInventory'], $pack));
            $pack = pack("NNC*", -1, $role) . $this->gamed->marshal($params["equipment"], $this->data['role']['equipment']);
            $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['putRoleEquipment'], $pack));
            $pack = pack("NNC*", -1, $role) . $this->gamed->marshal($params["storehouse"], $this->data['role']['storehouse']);
            $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['putRoleStoreHouse'], $pack));
            $pack = pack("NNC*", -1, $role) . $this->gamed->marshal($params["task"], $this->data['role']['task']);
            return $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['putRoleTask'], $pack));
        }
    }
    /**
     * Saves a data of character by structure
     * @params string $role
     * @params array $params
     * @return boolean
     */
    public function putJdRole($role, $params)
    {
        /*$pack = pack("NNC*", -1, $role, 1).$this->gamed->marshal($params, config('role'));
$this->gamed->SendToGamedBD($this->gamed->createHeader(config('code.putRole'), $pack));

return true;*/
    }
    /**
     * Send mail to the game mail
     * @params string $receiver
     * @params string $title
     * @params string $context
     * @params array $item
     * @params string $money
     * @return boolean
     */
    public function sendMail($receiver, $title, $context, $item = [], $money)
    {
        if ($item === []) {
            $item = ['id' => 0, 'pos' => 0, 'count' => 0, 'max_count' => 0, 'data' => '', 'proctype' => 0, 'expire_date' => 0, 'guid1' => 0, 'guid2' => 0, 'mask' => 0];
        }
        $pack = pack("NNCN", 344, 1025, 3, $receiver) . $this->gamed->packString($title) . $this->gamed->packString($context);
        $pack .= $this->gamed->marshal($item, $this->data['role']['pocket']['inv']);
        $pack .= pack("N", $money);
        return $this->gamed->SendToDelivery($this->gamed->createHeader($this->data['code']['sendMail'], $pack));
    }
    /**
     * Send message to the game chat on the special channel
     * @params string $role
     * @params string $msg
     * @params string $chanel
     * @return boolean
     */
    public function pwchat($role, $msg, $channel)
    {
        $pack = pack("CCN", $channel, 0, $role) . $this->gamed->packString($msg) . $this->gamed->packOctet('');
        return $this->gamed->SendToProvider($this->gamed->createHeader($this->data['code']['worldChat'], $pack));
    }
    /**
     * The ban of Account
     * @params string $role
     * @params miax $time
     * @params string $reason
     * @return boolean
     */
    public function forbidAcc($role, $time, $reason)
    {
        $pack = pack("N*", -1, 0, $role, $time) . $this->gamed->packString($reason);
        return $this->gamed->SendToDelivery($this->gamed->createHeader($this->data['code']['forbidAcc'], $pack));
    }
    /**
     * The ban of character
     * @params string $role
     * @params miax $time
     * @params string $reason
     * @return boolean
     */
    public function forbidRole($role, $time, $reason)
    {
        $pack = pack("N*", -1, 0, $role, $time) . $this->gamed->packString($reason);
        return $this->gamed->SendToDelivery($this->gamed->createHeader($this->data['code']['forbidRole'], $pack));
    }
    /**
     * The ban chat of account
     * @params string $role
     * @params miax $time
     * @params string $reason
     * @return boolean
     */
    public function muteAcc($role, $time, $reason)
    {
        $pack = pack("N*", -1, 0, $role, $time) . $this->gamed->packString($reason);
        return $this->gamed->SendToDelivery($this->gamed->createHeader($this->data['code']['muteAcc'], $pack));
    }
    /**
     * The ban chat of character
     * @params string $role
     * @params miax $time
     * @params string $reason
     * @return boolean
     */
    public function muteRole($role, $time, $reason)
    {
        $pack = pack("N*", -1, 0, $role, $time) . $this->gamed->packString($reason);
        return $this->gamed->SendToDelivery($this->gamed->createHeader($this->data['code']['muteRole'], $pack));
    }
    /**
     * Returns the ID of role
     * @params string $rolename
     * @return string
     */
    public function getRoleid($rolename)
    {
        $pack = pack("N", -1) . $this->gamed->packString($rolename) . pack("C", 1);
        $data = $this->gamed->deleteHeader($this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['getRoleid'], $pack)));
        $var = unpack("l", $data);
        if ($var[1] !== -1) {
            $var = unpack("N", $data);
        }
        return $var[1];
    }
    /**
     * Renaming a character
     * @params string $role
     * @params string $newname
     * @return boolean
     */
    public function renameRole($role, $oldname, $newname)
    {
        $pack = pack("N*", -1, $role) . $this->gamed->packString($oldname) . $this->gamed->packString($newname);
        return $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['renameRole'], $pack));
    }
    /**
     * Returns the array with online roles by structure
     * @return array
     */
    public function getOnlineList()
    {
        $online = [];
        if ($this->online) {
            $id = 0;
            $pack = pack('N*', -1, 1, $id) . $this->gamed->packString('1');
            $pack = $this->gamed->createHeader(352, $pack);
            $send = $this->gamed->SendToDelivery($pack);
            $data = $this->gamed->deleteHeader($send);
            $data = $this->gamed->unmarshal($data, $this->data['RoleList']);
            if (isset($data['users'])) {
                foreach ($data['users'] as $user) {
                    $online[] = $user;
                    //$id = $this->gamed->MaxOnlineUserID( $data['users'] );
                }
            }
        }
        return $online;
    }
    /**
     * Returns the array with friends by structure
     * @param string $role
     * @return array
     */
    public function getRoleFriends($role)
    {
        $tmp = 0;
        $pack = pack("N*", -1, $role);
        $data = $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['getRoleFriend'], $pack));
        $this->gamed->unpackCuint($data, $tmp);
        $this->gamed->unpackCuint($data, $tmp);
        $data = substr($data, $tmp + 5);
        return $this->gamed->unmarshal($data, $this->data['FriendList']);
    }
    public function addFaction($roleid, $name, $fid)
    {
        $pack = pack("N*", -1) . $this->gamed->packString($name) . pack("NN", $roleid, $fid);
        $pack = $this->gamed->createHeader($this->data['code']['AddFaction'], $pack);
        return $this->gamed->SendToGamedBD($pack);
    }
    public function delFaction($fid)
    {
        $pack = pack("N*", -1, $fid);
        $pack = $this->gamed->createHeader($this->data['code']['DelFaction'], $pack);
        return $this->gamed->SendToGamedBD($pack);
    }
    public function upgradeFaction($roleid, $fid, $level)
    {
        $pack = pack("N*", -1, $fid, $roleid, 0) . pack("C", $level);
        $pack = $this->gamed->createHeader($this->data['code']['FactionUpgrade'], $pack);
        return $this->gamed->SendToGamedBD($pack);
    }
    public function getFactionInfo($id)
    {
        $pack = pack("N*", -1, $id);
        $pack = $this->gamed->createHeader($this->data['code']['getFactionInfo'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        return $this->gamed->unmarshal($data, $this->data['FactionInfo']);
    }
    public function getFactionDetail($id)
    {
        $pack = pack("N*", -1, $id);
        $pack = $this->gamed->createHeader($this->data['code']['getFactionDetail'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        return $this->gamed->unmarshal($data, $this->data['FactionDetail']);
    }
    public function getFactionFortressDetail($id)
    {
        /*$pack = pack("N*", -1, $id);
$pack = $this->gamed->createHeader(config('code.GFactionFortressDetail'), $pack);
$send = $this->gamed->SendToGamedBD($pack);
$data = $this->gamed->deleteHeader($send);
return $this->gamed->unmarshal($data, config('FactionFortressDetail'));*/
    }
    public function getTerritories()
    {
        $pack = pack("N*", -1, 1);
        $data = $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['getTerritory'], $pack));
        $length = 0;
        $this->gamed->unpackCuint($data, $length);
        $this->gamed->unpackCuint($data, $length);
        $length += 6;
        $data = substr($data, $length);
        return $this->gamed->unmarshal($data, $this->data['GTerritoryDetail']);
    }
    public function getRaw($table, $handler = '', $key = '')
    {
        $pack = pack("N*", -1) . $this->gamed->packLongOctet($table) . $this->gamed->packOctet($handler) . $this->gamed->packOctet($key);
        $data = $this->gamed->deleteHeader($this->gamed->SendToGamedBD($this->gamed->createHeader(3055, $pack)));
        return $this->gamed->unmarshal($data, $this->data['RawRead']);
    }
    public function parseOctet($octet, $name)
    {
        $data = pack("H*", $octet);
        return $this->gamed->unmarshal($data, $this->data['octet'][$name]);
    }
    public function getUserFaction($id)
    {
        $tmp = 0;
        $pack = pack("N*", -1, 1, $id);
        $data = $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['getUserFaction'], $pack));
        $this->gamed->unpackCuint($data, $tmp);
        $this->gamed->unpackCuint($data, $tmp);
        $data = substr($data, $tmp + 8);
        return $this->gamed->unmarshal($data, $this->data['getUserFaction']);
    }
    public function generateSkill($params = [])
    {
        $skills = substr($params['skills'], 8);
        $id = isset($params['id']) ? dechex($params['id']) : 1;
        $level = isset($params['level']) ? dechex($params['level']) : 1;
        $progress = isset($params['progress']) ? dechex($params['progress']) : 0;
        $skills .= $this->gamed->reverseOctet($this->gamed->hex2octet($id));
        $skills .= $this->gamed->reverseOctet($this->gamed->hex2octet($progress));
        $skills .= $this->gamed->reverseOctet($this->gamed->hex2octet($level));
        $count = dechex(strlen($skills) / 24);
        $skills = $this->gamed->reverseOctet($this->gamed->hex2octet($count)) . $skills;
        return $skills;
    }
    public function serverOnline()
    {
        return @fsockopen(setting('server_ip', '127.0.0.1'), config('pw-api.ports.client'), $errCode, $errStr, 1) ? true : false;
    }
    public function ports()
    {
        $ports = [];
        $port_list = config('pw-api.ports');
        foreach ($port_list as $name => $port) {
            $ports[$name]['port'] = $port;
            $ports[$name]['open'] = @fsockopen(setting('server_ip', '127.0.0.1'), $port, $errCode, $errStr, 1) ? true : false;
        }
        return $ports;
    }
}
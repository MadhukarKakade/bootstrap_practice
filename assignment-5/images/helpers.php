<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\SchedulerIntervalController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\Configuration;
use GuzzleHttp\Client;
use App\Models\Rules;
use Illuminate\Support\Facades\Crypt;
use App\Models\CustomerListLog;
use Symfony\Polyfill\Iconv as p;

function utf8_for_xml($string)
{
    return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
}

if (!function_exists('basicAuthGetCurl')) {
    function basicAuthGetCurl($url, $username, $password)
    {
        $response = Http::withBasicAuth($username, $password)->get($url);
        $xml = simplexml_load_string(utf8_for_xml($response->body()), 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);  // XML to Json
        $array = json_decode($json, true);// Json to array
         return $array;
    }
}

if (!function_exists('basicAuthGetCurlWithXML')) {
    function basicAuthGetCurlWithXML($url, $username, $password)
    {
        $response = Http::withBasicAuth($username, $password)->get($url);
        $xml = simplexml_load_string(utf8_for_xml($response->body()), 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);  // XML to Json
        $array = json_decode($json, true);// Json to array
        return ['data'=>$array,'xmlResponse'=>$response->body()];
    }
}

if (!function_exists('brandsAPICurl')) {
    function brandsAPICurl($url, $post)
    {
        //print_r($url); print_r($post); exit;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }
}

if (!function_exists('removeSpecialChar')) {
    function removeSpecialChar($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

        return preg_replace('/-+/', ' ', $string);
    }
}

if (!function_exists('getApiDetails')) {
    function getApiDetails($code)
    {
        $apiDetails = [];
        $data = Configuration::where('code', $code)->first();
        if (empty($data)) {
            return $apiDetails;
        }
        if ($data['mode'] == 'prod') {
            $username = $data['live_username'];
            $password = Crypt::decryptString($data['live_password']);
        } else {
            $username = $data['test_username'];
            $password = Crypt::decryptString($data['test_password']);
        }
        $endpoint = $data['end_point'];
        $public_key = Crypt::decryptString($data['public_key']);
        $private_key = Crypt::decryptString($data['private_key']);
        $apiDetails = ['mode'=>$data['mode'] ,'username'=> $username,'password'=> $password,'endpoint'=> $endpoint,'public_key'=>$public_key,'private_key'=>$private_key];
        return $apiDetails;
    }
}

if (!function_exists('getRulesDetails')) {
    function getRulesDetails($code)
    {
        $data = Rules::where('code', $code)->first();
        $value = '';
        if ($data) {
            // $name = $data['name'];
            // codacy unused code
            $value = $data['value'];
        }
        return $value;
    }
}

if (!function_exists('getTrackingLink')) {
    function getTrackingLink($code, $number)
    {
        if(!empty($code)){
            $code = preg_replace('/\s+/', '_', Str::lower($code));
        }
        $data = Rules::where('code', $code)->first();
        if ($data) {
            $href = str_replace('{{{number}}}', $number, $data['value']);
            return '<a class="text-lapine-blue"  target="_blank" data-toggle="tooltip" data-placement="top" title="' . Str::upper(trim($data['name'])) . '" href="'.$href.'" id="' . $number . '">' . $number . '</a>';
        } else {
            return $number;
        }
    }
}

if (!function_exists('getbrandTrackingLink')) {
    function getbrandTrackingLink($code, $number)
    {
        $data = Rules::where('code', $code)->first();
        if ($data) {
            $href = str_replace('{{{number}}}', $number, $data['value']);
            return $href;
        } else {
            $href = "";
            return $number;
        }
    }
}



if (!function_exists('pre')) {
    function pre($data)
    {
        $pre = '';
        $pre .= "<pre>";
        $pre .= print_r($data);
        $pre .= "</pre>";
        return $pre;
    }
}

function changeDateFormate($date, $date_format = 'M d, Y H:i A')
{
    if (!empty($date)) {
        return Carbon::parse($date)->format($date_format);
    }
    return '';
}

if (!function_exists('appData')) {
    function appData()
    {
        $appData = [
            'name' => '',
            'description' => '',
            'logo' => 'active',
            'status' => 'active',
            'url' => '',
            'timezone' => config('app.timezone'),
        ];

        return $appData;
    }
}

if (!function_exists('getMenus')) {
    function getMenus($orderBy = 'updated_at')
    {
        $menuCtrl = new MenuController;
        $data = $menuCtrl->getMenus($orderBy);
        return $data;
    }
}

if (!function_exists('trimExcelStr')) {
    function trimExcelStr($string)
    {
        return trim(iconv("UTF-8", "ISO-8859-1", $string), " \t\n\r\0\x0B\xA0");
    }
}

//---- Format Date  - Change time zone from UTC to EST
if (!function_exists('formatDate')) {
    function formatDate($input_date, $format = '')
    {
        $original_time_zone = config('app.timezone');
        $output_time_zone = config('app.timezone');

        // Check if timezone is empty
        // then return input date
        if (empty($output_time_zone)) {
            return $input_date;
        }
        // Check if date format is empty
        // then set deafult date format
        if (empty($format)) {
            $format = 'M d, Y h:i A';
        }
        if (empty($input_date)) {
            return "";
        }
        // timezone conversion
        $date = new DateTime($input_date, new DateTimeZone($original_time_zone));
        $date->setTimezone(new DateTimeZone($output_time_zone));
        $time = $date->format($format);
        return $time;
    }
}


//---- Format Date CET - Change time zone from CET to EST
if (!function_exists('formatDateCET')) {
    function formatDateCET($input_date, $format = '')
    {
        $original_time_zone = 'Europe/Berlin';
        $output_time_zone = config('app.timezone');
        // Check if timezone is empty
        // then return input date

        if (empty($output_time_zone)) {
            return $input_date;
        }
        // Check if date format is empty
        // then set deafult date format
        if (empty($format)) {
            $format = 'M d, Y h:i A';
        }
        if (empty($input_date)) {
            return "";
        }
        // timezone conversion
        $date = new DateTime($input_date, new DateTimeZone($original_time_zone));
        $date->setTimezone(new DateTimeZone($output_time_zone));
        $time = $date->format($format);
        // dd($time);
        return $time;
    }
}


//---- Format Date CET - Change time zone from  EST to CET
if (!function_exists('formatESTtoCET')) {
    function formatESTtoCET($input_date, $format = '')
    {
        $original_time_zone = config('app.timezone');
        $output_time_zone = 'Europe/Berlin' ;
        // Check if timezone is empty
        // then return input date

        if (empty($output_time_zone)) {
            return $input_date;
        }
        // Check if date format is empty
        // then set deafult date format
        if (empty($format)) {
            $format = 'M d, Y h:i A';
        }
        if (empty($input_date)) {
            return "";
        }
        // timezone conversion
        $date = new DateTime($input_date, new DateTimeZone($original_time_zone));
        $date->setTimezone(new DateTimeZone($output_time_zone));
        $time = $date->format($format);
        // dd($time);
        return $time;
    }
}

/**
 * Format EST Date
 */
if (!function_exists('formatDateStr')) {
    function formatDateStr($date, $format = 'M d, Y')
    {
        $date = new DateTime($date);
        return $date->format($format);
    }
}

/**
 * Get Key List from a given Object
 */
if (!function_exists('getKeyList')) {
    function getKeyList($list, $key)
    {
        $response = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $response[] = $value[$key];
            }
        }
        return $response;
    }
}

/**
 * Get Today Weekday
 */
if (!function_exists('todayWeekDay')) {
    function todayWeekDay($operation = "", $value = 0)
    {
        $weekMap = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        if ($value) {
            $dayOfTheWeek = $operation == "+" ? Carbon::today()->addDays($value)->dayOfWeek : Carbon::today()->subDays($value)->dayOfWeek;
        // $dayOfTheWeek = $operation == "+" ? Carbon::today()->addWeekdays($value)->dayOfWeek : Carbon::today()->subWeekdays($value)->dayOfWeek;
        } else {
            $dayOfTheWeek = Carbon::today()->dayOfWeek;
        }

        return $weekMap[$dayOfTheWeek];
    }
}

/**
 * Get Today Date
 */
if (!function_exists('todayDate')) {
    function todayDate($operation = "", $value = 0, $format = 'Y-m-d')
    {
        $date = Carbon::today();

        if ($value) {
            $date = $operation == "+" ? Carbon::today()->addDays($value)->format($format) : Carbon::today()->subDays($value)->format($format);
        } else {
            $date = $date->format($format);
        }

        return $date;
    }
}


if (!function_exists('UTCtoEST')) {
    function UTCtoEST($date, $format)
    {
        $date = new DateTime($date, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $mydate = $date->format($format);
        return $mydate;
    }
}

/**
 * Format Excel Date
 */
if (!function_exists('transformExcelDate')) {
    function transformExcelDate($value)
    {
        $response = "";

        try {
            $response = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->toDateString();
        } catch (\Throwable $th) {
            $response = "";
        }

        return $response;
    }
}

/**
 * Format Excel Time
 */
if (!function_exists('transformExcelTime')) {
    function transformExcelTime($value)
    {
        $response = "";

        try {
            $response = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->toTimeString();
        } catch (\Throwable $th) {
            $response = "";
        }

        return $response;
    }
}

if (!function_exists('transformExcelDateTime')) {
    function transformExcelDateTime($value)
    {
        $response = "";

        try {
            $response = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->toDateTimeString();
        } catch (\Throwable $th) {
            $response = "";
        }

        return $response;
    }
}

//get title
if (!function_exists('getPageTitle')) {
    function getPageTitle()
    {
        //$str = 'business-rules.create';
        // $strOutput = 'Business Rules/Create';
        // $finalOutput= "Create Business Rules - Effectus Engage";

        $str = Route::currentRouteName();

        if (strpos($str, '.') > 0) {
            $sub = substr($str, 0, strpos($str, '.'));
            $action = substr($str, strpos($str, '.') + 1);
        } else {
            $sub = $str;
            $action = "";
        }

        $up = str_replace("-", " ", $sub);
        if ($action == "") {
            $concat = $up;
        } else {
            $concat = $action . "-" . $up;
        }
        $replace = str_replace("index-", " ", $concat);
        //$title = Str::title($replace);

        $title = $replace;

        // return $title . '-' . config('app.name', 'app-name');
        return $title;
    }
}

/**
 * Transform To Key Value
 */
if (!function_exists('toKeyValue')) {
    function toKeyValue($list, $keyName, $valueName)
    {
        $response = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $response[$value[$keyName]] = $value[$valueName];
            }
        }
        return $response;
    }
}

/**
 * Parse XML to JSON
 */
if (!function_exists('xmlToJson')) {
    function xmlToJson($xml)
    {
        $response = [];
        if ($xml) {
            $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $xml);
            $response = simplexml_load_string($clean_xml);
        }
        return $response;
    }
}

//get entity name
if (!function_exists('getEntityName')) {
    function getEntityName($id)
    {
        //$id = hasValues($id);
        if ($id) {
            return '';
        } else {
            $entityname = new EntityController;
            $data = $entityname->getEntityName($id);
            $entt = implode(', ', array_column($data, 'name'));
            return ucwords($entt);
        }
    }
}
//get user name
if (!function_exists('getUserName')) {
    function getUserName($id = 'id')
    {
        $username = new TemplateController;
        $data = $username->getUserName($id);
        return $data['name'];
    }
}

//get attribute name
if (!function_exists('getAttributeName')) {
    function getAttributeName($id = 'id')
    {
        if (empty($id) || empty($id[0])) {
            return '';
        } else {
            $attributename = new AttributeController;
            $data = $attributename->getAttributeName($id);
            $attr = implode(', ', array_column($data, 'name'));
            return ucwords($attr);
        }
    }
}
//get schedular interval name
if (!function_exists('getSchedularIntervalName')) {
    function getSchedularIntervalName($id = 'id')
    {
        $schedularIntervalName = new SchedulerIntervalController;
        $data = $schedularIntervalName->getSchedularIntervalName($id);
        $attr = implode(', ', array_column($data, 'name'));
        return ucwords($attr);
    }
}

//get template name
if (!function_exists('getTemplateName')) {
    function getTemplateName($id = 'id')
    {
        $templateName = new TemplateController;
        $data = $templateName->getTemplateName($id);
        $templateName = implode(', ', array_column($data, 'name'));
        return ucwords($templateName);
    }
}

//get edit rule loh html result
if (!function_exists('getHtmlData')) {
    function getHtmlData($diffWithJsonData, $changesOld, $changesAttributes, $html = "")
    {
        static $sr = 0;
        foreach ($diffWithJsonData as $key => $value) {
            if (in_array($key, ['helpers', 'options'])) {
                continue;
            }
            if (empty($value)) {
                continue;
            }

            if (!is_array($value) || in_array($key, ['entity_ids', 'attribute_ids', 'options'])) {
                if ($key == 'created_by' || $key == 'updated_by') {
                    $changesOld[$key] = getUserName($changesOld[$key]);
                    $changesAttributes[$key] = getUserName($changesAttributes[$key]);
                }
                if ($key == 'entity_ids' || $key == 'entity_id') {
                    if (!empty($changesOld[$key])) {
                        $changesOld[$key] = getEntityName($changesOld[$key]);
                    } else {
                        $changesOld[$key] = '';
                    }
                    if (!empty($changesAttributes[$key])) {
                        $changesAttributes[$key] = getEntityName($changesAttributes[$key]);
                    } else {
                        $changesAttributes[$key] = '';
                    }
                }
                if ($key == 'attribute_ids' || $key == 'attribute_id') {
                    if (!empty($changesOld[$key])) {
                        $changesOld[$key] = getAttributeName($changesOld[$key]);
                    } else {
                        $changesOld[$key] = '';
                    }

                    if (!empty($changesAttributes[$key])) {
                        $changesAttributes[$key] = getAttributeName($changesAttributes[$key]);
                    } else {
                        $changesAttributes[$key] = '';
                    }
                }
                if ($key == 'scheduler_interval_id') {
                    $changesOld[$key] = getSchedularIntervalName($changesOld[$key]);
                    $changesAttributes[$key] = getSchedularIntervalName($changesAttributes[$key]);
                }

                if ($key == 'template_id') {
                    $changesOld[$key] = getTemplateName($changesOld[$key]);
                    $changesAttributes[$key] = getTemplateName($changesAttributes[$key]);
                }

                $string = $key;

                $result = str_replace('_', ' ', str_replace('_id', '', $string));

                if (empty($changesOld[$key]) && empty($changesAttributes[$key])) {
                    continue;
                }
                $htmData = '';
                $srNo = $sr + 1;
                $htmData .= '<tr>
                        <td class="text-right numbers-font numbers-size" style="color: #212529;"> ' . $srNo . '</td>
                        <td style="color: #212529;"> ' . ucfirst($result) . '</td>';
                if (is_array($changesOld[$key])) {
                    $htmData .= '<td>' . implode(', ', $changesOld[$key]) . '</td>';
                } else {
                    $htmData .= '<td>' . $changesOld[$key] . '</td>';
                }
                if (is_array($changesAttributes[$key])) {
                    $htmData .= '<td>' . implode(', ', $changesAttributes[$key]) . '</td>';
                } else {
                    $htmData .= '<td>' . $changesAttributes[$key] . '</td>';
                }
                $htmData .= '</tr>';
            } else {
                if (is_array($value) && count($value) == 1) {
                    $keyValKeys = array_keys($value);
                    if (is_numeric($keyValKeys[0])) {
                        $string = $key;
                        $result = str_replace('_', ' ', str_replace('_id', '', $string));
                        $htmData = '';
                        $srNo = $sr + 1;
                        $htmData .= '<tr>
                        <td class="text-right numbers-font numbers-size"> ' . $srNo . '</td>
                        <td> ' . ucfirst($result) . '</td>';
                        if (empty($changesOld[$key])) {
                            $htmData .='<td>-</td>';
                        } else {
                            $htmData .='<td>' . $changesOld[$key][0] . '</td>';
                        }
                        if (empty($changesAttributes[$key])) {
                            $htmData .='<td>-</td>';
                        } else {
                            $htmData .='<td>' . $changesAttributes[$key][0] . '</td>';
                        }
                        $htmData .='</tr>';
                    }
                }
                if (empty($changesOld[$key])) {
                    $changesOld[$key] = [];
                }
                if (empty($changesAttributes[$key])) {
                    $changesAttributes[$key] = [];
                }
                $htmData .= getHtmlData($value, $changesOld[$key], $changesAttributes[$key], $html);
            }
        }
        return $htmData;
    }
}

// check if complete array is blank of not
if (!function_exists('hasValues')) {
    function hasValues($array)
    {
        foreach (array_unique($array) as $k => $value) {
            if (empty($value)) {
                unset($array[$k]);
            }
        }

        if (!empty($array)) {
            return $array;
        } else {
            return false;
        }
    }
}

/**
 * Validate a Phone Number
 */
if (!function_exists('validate_phone_number')) {
    function validate_phone_number($phone)
    {
        // Allow +, - and . in phone number
        $filtered_phone_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
        // Remove "-" from number
        $phone_to_check = str_replace("-", "", $filtered_phone_number);
        // Check the lenght of number
        // This can be customized if you want phone number from a specific country
        if (strlen($phone_to_check) < 10 || strlen($phone_to_check) > 14) {
            return false;
        } else {
            return preg_replace('/[^\dxX]/', '', $phone_to_check);
        }
    }
}

/**
 * Get Today Weekday
 */
if (!function_exists('checkIfDay')) {
    function checkIfDay($day)
    {
        $weekMap = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];

        $response = false;

        if (in_array(strtolower($day), $weekMap)) {
            $response = true;
        }

        return $response;
    }
}

/**
 * Get page title as set as on the menus
 */
if (!function_exists('getMenuDisplay')) {
    function getMenuDisplay()
    {
        // $name = Route::currentRouteName();
        $menuCtrl = new MenuController;
        $menu = $menuCtrl->getMenuDisplay(Route::currentRouteName());
        if (empty($menu->display_name)) {
            return str_ireplace('Adm','adm',ucwords(getPageTitle()));
        } else {
            return str_ireplace('Adm','adm',ucwords($menu->display_name));
        }
    }
}


if (!function_exists('getMenuPermission')) {
    function getMenuPermission()
    {
        // $name = Route::currentRouteName();
        $menuCtrl = new MenuController;
        $menu = $menuCtrl->getMenuDisplay(Route::currentRouteName());

        if (empty($menu->name)) {
            return ucwords(getPageTitle());
        } else {
            return ucwords($menu->name);
        }
    }
}

if (!function_exists('getBreadcrumb')) {
    function getBreadcrumb()
    {
        // $name = Route::currentRouteName();
        $breadcrumb = '';
        $menuCtrl = new MenuController;
        $menu = $menuCtrl->getBreadcrumb(Route::currentRouteName());
        if (empty($menu->display_name)) {
//            return ucwords(getPageTitle());
            $breadcrumb .= "<li class='breadcrumb-item '>".str_ireplace('Adm','adm',ucwords(getPageTitle()))."</li>";
        } else {

            if(!empty($menu->main_manu)){
                $breadcrumb = "<li class='breadcrumb-item '>".str_ireplace('Adm','adm',ucwords($menu->main_manu))." </li>";
            }
            $breadcrumb .= "<li class='breadcrumb-item '>".str_ireplace('Adm','adm',ucwords($menu->display_name))."</li>";
//             $breadcrumb = $menu->main_manu.' / '.$menu->display_name;
//            return $breadcrumb;
        }
        return $breadcrumb;
    }
}

if (!function_exists('getMenuIcon')) {
    function getMenuIcon()
    {
        // $name = Route::currentRouteName();
        $menuCtrl = new MenuController;
        $menu = $menuCtrl->getMenuDisplay(Route::currentRouteName());
        if (empty($menu->icon)) {
            return 'far fa-circle';
        } else {
            return $menu->icon;
        }
    }
}

/**
 * Check if Object exists in Array
 */
if (!function_exists('object_in_array')) {
    function object_in_array($haystack, $needle)
    {
        $response = false;

        foreach ($haystack as $haystack_value) {
            if (empty(array_diff_assoc((array) $haystack_value, (array) $needle))) {
                $response = true;
            }
        }

        return $response;
    }
}

if (!function_exists('getMenuName')) {
    function getMenuName($routeName = '')
    {
        $menuCtrl = new MenuController;
        $data = $menuCtrl->getMenuName($routeName);
        return $data;
    }
}

if( !function_exists('cleanSpedialChars')){
    /**
     * Removes the all accurance of special characters
     * Date: 25-09-2021
     * Parag Chaure
     */
    function cleanSpedialChars($text){
        return preg_replace('/[^a-zA-Z0-9\ ]/m', '', $text);
    }
}

if( !function_exists('cleanSpedialCharsInv')){
    /**
     * Removes the all accurance of special characters
     * Date: 25-09-2021
     * Parag Chaure
     */
    function cleanSpedialCharsInv($text){
        return preg_replace('/[^a-zA-Z0-9\ \-]/m', '', $text);
    }
}


if (!function_exists('trimExcelValue')) {
    function trimExcelValue($value)
    {
        return trim(iconv("UTF-8", "ISO-8859-1", $value), " \t\n\r\0\x0B\xA0\u200C");
    }
}

if (!function_exists('trimExcelPrintableValue')) {
    function trimExcelPrintableValue($value)
    {
        return trim(preg_replace('/[[:^print:]]/', "", $value));
    }
}



if( !function_exists('validateGS1Data')){
    function validateGS1Data($customerList){
        // $gsDataArray = [];
        // codacy unused code
        $customerList = explode(',',$customerList);
        $faiShipmentController = new \App\Http\Controllers\FaiShipmentController();
        $orderData = $faiShipmentController->getFaiShipmentMasterDataRds($customerList);
        //dd($orderData);
        if (!empty($orderData) && count($orderData) != 0) {
            foreach ($orderData as $data) {
                if( isset($data->STREET) && empty( trim($data->STREET) ) ){
                    return 'Ship to name entries could not be blank';
                }
                if( isset($data->STORE) && empty( trim($data->STORE) ) ){
                    return 'Ship to compay entries could not be blank';
                }
                if( isset($data->WEIGHT) && empty( trim($data->WEIGHT) ) ){
                    return 'Item weight must be greater than zero';
                }
                if( isset($data->LENGTH) && empty( trim($data->LENGTH) ) ){
                    return 'Item length must be greater than zero';
                }
                if( isset($data->WIDTH) && empty( trim($data->WIDTH) ) ){
                    return 'Item width must be greater than zero';
                }
                if( isset($data->HEIGHT) && empty( trim($data->HEIGHT) ) ){
                    return 'Item height must be greater than zero';
                }
            }
        }
        return 'true';
    }
}


if (!function_exists('lightGalleryScript')) {
    function lightGalleryScript($unqNumber, $images)
    {
        $script = '';
        $script .= '<div><script type="text/javascript">
        $(document).ready(function() {
                $(document).on("click", "#dynamic' . $unqNumber . '", function(){
                $(this).lightGallery({
                    dynamic: true,
                    dynamicEl: [';
        if (is_array($images)) {
            foreach ($images as $image) {
                $script .= '{
                        "src": "' . $image . '",
                        "thumb": "' . $image . '",
                    },';
            }
        } else {
            $script .= '{
            "src": "' . $images . '",
            "thumb": "' . $images . '",
        }';
        }

        $script .= ']
                })

            });
        });
    </script></div>';

        return $script;
    }

}

/**
 * Check for lapine user
 */
if(!function_exists('secondLayerSecurityCheck')){
    function secondLayerSecurityCheck($email)
    {
        // $SECOND_LAYER_SECURITY = config('constants.SECOND_LAYER_SECURITY');
        // if(!empty($email)){
        //     $domain_name = substr(strrchr($email, "@"), 1);
        //     if(in_array("@".strtolower($domain_name) ,$SECOND_LAYER_SECURITY)){
        //         return true;
        //     }
        // }
        // return false;

        //Dynamic Rule setting for second layer security check

        $rule = Rules::select('value')->where('code', 'second_layer_security')->first();
        $ruleArray = explode(',',preg_replace('/\s+/', '', $rule->value));
        if(!empty($email) && !empty($ruleArray)){
            $domain_name = substr(strrchr($email, "@"), 1);
            if(in_array("@".strtolower($domain_name) ,$ruleArray)){
                return true;
            }
        }
        return false;
    }
}

/** sha512 Hashing */
if(!function_exists('sha512')){
    function sha512($string) {
        return hash('sha512', $string);
    }
}


if(!function_exists('trackingCurl')){
    function trackingCurl($endpoint, $header,$mothod= 'GET')
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "$endpoint",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $mothod,
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $result = [];
        if ($err) {
            $result['response'] =  "cURL Error #:" . $err;
            $result['success'] = false;
        } else {
            $result['response'] = $response;
            $result['success'] = true;
            return $result;
        }
    }

    if (!function_exists('getHoursList')) {
        function getHoursList($start,$end,$interval){
            $period = new DatePeriod(
                new DateTime($start),
                new DateInterval($interval),
                new DateTime($end)
            );
            return $period;
        }

    }

    if (!function_exists('getDaysList')) {
        function getDaysList(){
            return array_map(function ($day) {    return date_create('Sunday')->modify("+$day day")->format('l');},    range(0, 6));
        }

    }

    if (!function_exists('getRoutesList')) {
        function getRoutesList(){
            $routeCollection = Route::getRoutes();
            $availableRoutes = [];
                foreach ($routeCollection as $value) {
                    //dd($value->action['prefix']);
                    if ($value->getName() && (!Str::contains($value->getName(), 'api.')) && (!Str::contains($value->getName(), 'api/')) && (!Str::contains($value->uri(), '_ignition')) && $value->methods()[0] == 'GET' && ($value->action['prefix'] == '/crons')) {
                        $availableRoutes[] = $value->getName();
                    }
                }
            asort($availableRoutes);
            return $availableRoutes;
        }

    }
    if (!function_exists('getCronFrequencyList')) {
        function getCronFrequencyList(){
            //$cronList = array('everyMinute','everyTwoMinutes','everyThreeMinutes','everyFourMinutes','everyFiveMinutes','everyTenMinutes','everyFifteenMinutes','everyThirtyMinutes','hourly','hourlyAt','everyTwoHours','everyThreeHours','everyFourHours','everySixHours','daily','dailyAt','twiceDaily','weekly','weeklyOn','monthly','monthlyOn','twiceMonthly','lastDayOfMonth','quarterly','yearly','yearlyOn');
            $cronList = array('everyMinute','everyTwoMinutes','everyThreeMinutes','everyFourMinutes','everyFiveMinutes','everyTenMinutes','everyFifteenMinutes','everyThirtyMinutes','hourly','everyTwoHours','everyThreeHours','everyFourHours','everySixHours','daily','dailyAt','twiceDaily','weekly','weeklyOn','monthly','monthlyOn','lastDayOfMonth','quarterly','yearly');
            //sort($cronList);
            return $cronList;
        }

    }
    if (!function_exists('getMonthList')) {
        function getMonthList(){
            $month = [];

            for ($m=1; $m<=12; $m++) {
                $month[] = date('F', mktime(0,0,0,$m, 1, date('Y')));
            }
            return  $month;
        }

    }

    if (!function_exists('getDateList')) {
        function getDateList(){
            $dateList = [];

            for ($m=1; $m<=31; $m++) {
                $dateList[] = $m;
            }
            return  $dateList;
        }

    }

    if (!function_exists('getWebstoreTrackingLink')) {
        function getWebstoreTrackingLink($code, $number)
        {
            $data = Rules::where('code', $code)->first();
            $linkArray=[];
            $linkArray['number'] = $number;
            $linkArray['link'] = '';
            if ($data) {
                $href = str_replace('{{{number}}}', $number, $data['value']);
                $linkArray['link'] = $href;
                //return '<a class="text-lapine-blue"  target="_blank" data-toggle="tooltip" data-placement="top" title="' . Str::upper($code) . '" href="'.$href.'" id="' . $number . '">' . $number . '</a>';
            }

            return json_encode($linkArray);
        }
    }

    if (!function_exists('invoice_num')) {
        function invoice_num ($input, $pad_len = 7, $prefix = null) {
            if (is_string($prefix))
                return sprintf("%s%s", $prefix, str_pad($input, $pad_len, "0", STR_PAD_LEFT));

            return str_pad($input, $pad_len, "0", STR_PAD_LEFT);
        }
    }

    if (!function_exists('avalaraAPICurl')) {
        function avalaraAPICurl($url, $post)
        {
            //print_r($url); print_r($post); exit;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => $post,
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }
    }


    if(!function_exists('purifyQueueJson')){
        function purifyQueueJson($queyjSon){
            $data = [];
            $pattern = '/([\:]+([\s]{0,1})+[\{])|([\:\{])+/m' ;
                if (preg_match($pattern, $queyjSon, $matches, PREG_OFFSET_CAPTURE)) {
                    $position = $matches[0][1];
                    if(!empty($position) && $position > 0){
                        $json = substr($queyjSon, $position+1 );
                        $jsonId = substr($queyjSon,0,$position);
                        if(empty($jsonId)){
                            $json = substr($queyjSon, $position );
                        }
                        $data['jsonRes'] = json_decode($json,true);
                        $data['jsonId'] = trim($jsonId);
                        return $data;
                    }
                }
            $data['jsonRes'] = json_decode($queyjSon,true);
            return $data;


        }
    }
}

<?php

namespace LisDev\Delivery;
use PrepareReturnData;
use Array2Xml;


/**
 * Nova Poshta API Class.
 *
 * @author lis-dev
 *
 * @see https://my.novaposhta.ua/data/API2-200215-1622-28.pdf
 * @see https://github.com/lis-dev
 *
 * @license MIT
 */
class NovaPoshtaApi2
{
    const API_URI = 'https://api.novaposhta.ua/v2.0';

    /**
     * Key for API NovaPoshta.
     *
     * @var string
     *
     * @see https://my.novaposhta.ua/settings/index#apikeys
     */
    protected $key;

    /**
     * @var bool Throw exceptions when in response is error
     */
    protected $throwErrors = false;

    /**
     * @var string Format of returned data - array, json, xml
     */
    protected $format = 'array';

    /**
     * @var string Language of response
     */
    protected $language = 'ru';

    /**
     * @var string Connection type (curl | file_get_contents)
     */
    protected $connectionType = 'curl';

    /** @var int Connection timeout (in seconds) */
    protected $timeout = 0;

    /**
     * @var string Areas (loaded from file, because there is no so function in NovaPoshta API 2.0)
     */
    protected $areas = '';

    /**
     * @var string Set current model for methods save(), update(), delete()
     */
    protected $model = 'Common';

    /**
     * @var string Set method of current model
     */
    protected $method = '';

    /**
     * @var array Set params of current method of current model
     */
    protected $params = array();

    /**
     * Default constructor.
     *
     * @param string $key            NovaPoshta API key
     * @param string $language       Default Language
     * @param bool   $throwErrors    Throw request errors as Exceptions
     * @param string   $connectionType Connection type (curl | file_get_contents)
     *
     * @return NovaPoshtaApi2
     */
    public function __construct($key, $language = 'ru', $throwErrors = false, $connectionType = 'curl')
    {
        $this->throwErrors = $throwErrors;
        $this
            ->setKey($key)
            ->setLanguage($language)
            ->setConnectionType($connectionType)
            ->model('Common');
    }

    /**
     * Setter for key property.
     *
     * @param string $key NovaPoshta API key
     *
     * @return NovaPoshtaApi2
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Getter for key property.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Setter for $connectionType property.
     *
     * @param string $connectionType Connection type (curl | file_get_contents)
     *
     * @return $this
     */
    public function setConnectionType($connectionType)
    {
        $this->connectionType = $connectionType;
        return $this;
    }

    /**
     * Getter for $connectionType property.
     *
     * @return string
     */
    public function getConnectionType()
    {
        return $this->connectionType;
    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int)$timeout;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Setter for language property.
     *
     * @param string $language
     *
     * @return NovaPoshtaApi2
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Getter for language property.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Setter for format property.
     *
     * @param string $format Format of returned data by methods (json, xml, array)
     *
     * @return NovaPoshtaApi2
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Getter for format property.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }



    /**
     * Make request to NovaPoshta API.
     *
     * @param string $model  Model name
     * @param string $method Method name
     * @param array  $params Required params
     */
    private function request($model, $method, $params = null)
    {
        // Get required URL
        $url = 'xml' == $this->format
            ? self::API_URI.'/xml/'
            : self::API_URI.'/json/';

        $data = array(
            'apiKey' => $this->key,
            'modelName' => $model,
            'calledMethod' => $method,
            'language' => $this->language,
            'methodProperties' => $params,
        );
        $result = array();

        $prepare = new PrepareReturnData();
        $array2xml = new Array2Xml();

        // Convert data to neccessary format
        $post = 'xml' == $this->format
            ? $array2xml->array2xml($data)
            : json_encode($data);

        if ('curl' == $this->getConnectionType()) {
            $ch = curl_init($url);
            if (is_resource($ch)) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: '.('xml' == $this->format ? 'text/xml' : 'application/json')));
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

                if ($this->timeout > 0) {
                    curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
                }

                $result = curl_exec($ch);
                curl_close($ch);
            }
        } else {
            $httpOptions = array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded;\r\n",
                'content' => $post,
            );

            if ($this->timeout > 0) {
                $httpOptions['timeout'] = $this->timeout;
            }

            $result = file_get_contents($url, false, stream_context_create(array(
                'http' => $httpOptions,
            )));
        }

        return $prepare->prepare($result);
    }

    /**
     * Set current model and empties method and params properties.
     *
     * @param string $model
     *
     * @return mixed
     */
    public function model($model = '')
    {
        if (!$model) {
            return $this->model;
        }

        $this->model = $model;
        $this->method = '';
        $this->params = array();
        return $this;
    }

    /**
     * Set method of current model property and empties params properties.
     *
     * @param string $method
     *
     * @return mixed
     */
    public function method($method = '')
    {
        if (!$method) {
            return $this->method;
        }

        $this->method = $method;
        $this->params = array();
        return $this;
    }

    /**
     * Set params of current method/property property.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function params($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Execute request to NovaPoshta API.
     *
     * @return mixed
     */
    public function execute()
    {
        return $this->request($this->model, $this->method, $this->params);
    }

    /**
     * Get tracking information by track number.
     *
     * @param string $track Track number
     *
     * @return mixed
     */
    public function documentsTracking($track)
    {
        $params = array('Documents' => array(array('DocumentNumber' => $track)));

        return $this->request('TrackingDocument', 'getStatusDocuments', $params);
    }

    /**
     * Get cities of company NovaPoshta.
     *
     * @param int    $page         Num of page
     * @param string $findByString Find city by russian or ukrainian word
     * @param string $ref          ID of city
     *
     * @return mixed
     */
    public function getCities($page = 0, $findByString = '', $ref = '')
    {
        return $this->request('Address', 'getCities', array(
            'Page' => $page,
            'FindByString' => $findByString,
            'Ref' => $ref,
        ));
    }

    /**
     * Get warehouses by city.
     *
     * @param string $cityRef ID of city
     * @param int    $page
     *
     * @return mixed
     */
    public function getWarehouses($cityRef, $page = 0)
    {
        return $this->request('Address', 'getWarehouses', array(
            'CityRef' => $cityRef,
            'Page' => $page,
        ));
    }
    
    /**
     * Get warehouse types.
     *
     * @return mixed
     */
    public function getWarehouseTypes()
    {
        return $this->request('Address', 'getWarehouseTypes');
    }    

    /**
     * Get 5 nearest warehouses by array of strings.
     *
     * @param array $searchStringArray
     *
     * @return mixed
     */
    public function findNearestWarehouse($searchStringArray)
    {
        $searchStringArray = (array) $searchStringArray;
        return $this->request('Address', 'findNearestWarehouse', array(
            'SearchStringArray' => $searchStringArray,
        ));
    }

    /**
     * Get one warehouse by city name and warehouse's description.
     *
     * @param string $cityRef     ID of city
     * @param string $description Description like in getted by getWarehouses()
     *
     * @return mixed
     */
    public function getWarehouse($cityRef, $description = '')
    {
        $warehouses = $this->getWarehouses($cityRef);
        $error = array();
        $data = array();
        if (is_array($warehouses['data'])) {
            $data = $warehouses['data'][0];
            if (count($warehouses['data']) > 1 && $description) {
                foreach ($warehouses['data'] as $warehouse) {
                    if (false !== mb_stripos($warehouse['Description'], $description)
                    or false !== mb_stripos($warehouse['DescriptionRu'], $description)) {
                        $data = $warehouse;
                        break;
                    }
                }
            }
        }
        // Error
        (!$data) and $error = 'Warehouse was not found';
        // Return data in same format like NovaPoshta API
        return $this->prepare(
            array(
                'success' => empty($error),
                'data' => array($data),
                'errors' => (array) $error,
                'warnings' => array(),
                'info' => array(),
        )
        );
    }

    /**
     * Get streets list by city and/or search string.
     *
     * @param string $cityRef      ID of city
     * @param string $findByString
     * @param int    $page
     *
     * @return mixed
     */
    public function getStreet($cityRef, $findByString = '', $page = 0)
    {
        return $this->request('Address', 'getStreet', array(
            'FindByString' => $findByString,
            'CityRef' => $cityRef,
            'Page' => $page,
        ));
    }

    /**
     * Find current area in list of areas.
     *
     * @param array  $areas        List of arias, getted from file
     * @param string $findByString Area name
     * @param string $ref          Area Ref ID
     *
     * @return array
     */
    protected function findArea(array $areas, $findByString = '', $ref = '')
    {
        $data = array();
        if (!$findByString and !$ref) {
            return $data;
        }
        // Try to find current region
        foreach ($areas as $key => $area) {
            // Is current area found by string or by key
            $found = $findByString
                ? ((false !== mb_stripos($area['Description'], $findByString))
                    or (false !== mb_stripos($area['DescriptionRu'], $findByString))
                    or (false !== mb_stripos($area['Area'], $findByString))
                    or (false !== mb_stripos($area['AreaRu'], $findByString)))
                : ($key == $ref);
            if ($found) {
                $area['Ref'] = $key;
                $data[] = $area;
                break;
            }
        }
        return $data;
    }

    /**
     * Get area by name or by ID.
     *
     * @param string $findByString Find area by russian or ukrainian word
     * @param string $ref          Get area by ID
     *
     * @return array
     */
    public function getArea($findByString = '', $ref = '')
    {
        // Load areas list from file
        empty($this->areas) and $this->areas = (include dirname(__FILE__).'/NovaPoshtaApi2Areas.php');
        $data = $this->findArea($this->areas, $findByString, $ref);
        // Error
        $error = array();
        empty($data) and $error = array('Area was not found');
        // Return data in same format like NovaPoshta API
        return $this->prepare(
            array(
                'success' => empty($error),
                'data' => $data,
                'errors' => $error,
                'warnings' => array(),
                'info' => array(),
        )
        );
    }

    /**
     * Get areas list by city and/or search string.
     *
     * @param string $ref  ID of area
     * @param int    $page
     *
     * @return mixed
     */
    public function getAreas($ref = '', $page = 0)
    {
        return $this->request('Address', 'getAreas', array(
            'Ref' => $ref,
            'Page' => $page,
        ));
    }

    /**
     * Find city from list by name of region.
     *
     * @param array  $cities   Array from query getCities to NovaPoshta
     * @param string $areaName
     *
     * @return array
     */
    protected function findCityByRegion($cities, $areaName)
    {
        $data = array();
        $areaRef = '';
        // Get region id
        $area = $this->getArea($areaName);
        $area['success'] and $areaRef = $area['data'][0]['Ref'];
        if ($areaRef and is_array($cities['data'])) {
            foreach ($cities['data'] as $city) {
                if ($city['Area'] == $areaRef) {
                    $data[] = $city;
                }
            }
        }
        return $data;
    }

    /**
     * Get city by name and region (if it needs).
     *
     * @param string $cityName City's name
     * @param string $areaName Region's name
     * @param string $warehouseDescription Warehouse description to identiry needed city (if it more than 1 in the area)
     *
     * @return array Cities's data Can be returned more than 1 city with the same name
     */
    public function getCity($cityName, $areaName = '', $warehouseDescription = '')
    {
        // Get cities by name
        $cities = $this->getCities(0, $cityName);
        $data = array();
        if (is_array($cities) && is_array($cities['data'])) {
            // If cities more then one, calculate current by area name
            $data = (count($cities['data']) > 1)
                ? $this->findCityByRegion($cities, $areaName)
                : array($cities['data'][0]);
        }
        // Try to identify city by one of warehouses descriptions
        if (count($data) > 1 && $warehouseDescription) {
            foreach ($data as $cityData) {
                $warehouseData = $this->getWarehouse($cityData['Ref'], $warehouseDescription);
                $warehouseDescriptions = array(
                    $warehouseData['data'][0]['Description'], 
                    $warehouseData['data'][0]['DescriptionRu']
                );
                if (in_array($warehouseDescription, $warehouseDescriptions)) {
                    $data = array($cityData);
                    break;
                }
            }
        }
        // Error
        $error = array();
        (!$data) and $error = array('City was not found');
        // Return data in same format like NovaPoshta API
        return $this->prepare(
            array(
                'success' => empty($error),
                'data' => $data,
                'errors' => $error,
                'warnings' => array(),
                'info' => array(),
        )
        );
    }

    /**
     * Magic method of calling functions (uses for calling Common Model of NovaPoshta API).
     *
     * @param string $method    Called method of Common Model
     * @param array  $arguments Array of params
     */
    public function __call($method, $arguments)
    {
        $common_model_method = array(
            'getTypesOfCounterparties',
            'getBackwardDeliveryCargoTypes',
            'getCargoDescriptionList',
            'getCargoTypes',
            'getDocumentStatuses',
            'getOwnershipFormsList',
            'getPalletsList',
            'getPaymentForms',
            'getTimeIntervals',
            'getServiceTypes',
            'getTiresWheelsList',
            'getTraysList',
            'getTypesOfAlternativePayers',
            'getTypesOfPayers',
            'getTypesOfPayersForRedelivery',
        );
        // Call method of Common model
        if (in_array($method, $common_model_method)) {
            return $this
                ->model('Common')
                ->method($method)
                ->params(null)
                ->execute();
        }
    }

    /**
     * Delete method of current model.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function delete($params)
    {
        return $this->request($this->model, 'delete', $params);
    }

    /**
     * Update method of current model
     * Required params:
     * For ContactPerson model: Ref, CounterpartyRef, FirstName (ukr), MiddleName, LastName, Phone (format 0xxxxxxxxx)
     * For Counterparty model: Ref, CounterpartyProperty (Recipient|Sender), CityRef, CounterpartyType (Organization, PrivatePerson),
     * FirstName (or name of organization), MiddleName, LastName, Phone (0xxxxxxxxx), OwnershipForm (if Organization).
     *
     * @param array $params
     *
     * @return mixed
     */
    public function update($params)
    {
        return $this->request($this->model, 'update', $params);
    }

    /**
     * Save method of current model
     * Required params:
     * For ContactPerson model (only for Organization API key, for PrivatePerson error will be returned):
     *	 CounterpartyRef, FirstName (ukr), MiddleName, LastName, Phone (format 0xxxxxxxxx)
     * For Counterparty model:
     *	 CounterpartyProperty (Recipient|Sender), CityRef, CounterpartyType (Organization, PrivatePerson),
     *	 FirstName (or name of organization), MiddleName, LastName, Phone (0xxxxxxxxx), OwnershipForm (if Organization).
     *
     * @param array $params
     *
     * @return mixed
     */
    public function save($params)
    {
        return $this->request($this->model, 'save', $params);
    }

    /**
     * getCounterparties() function of model Counterparty.
     *
     * @param string $counterpartyProperty Type of Counterparty (Sender|Recipient)
     * @param int    $page                 Page number
     * @param string $findByString         String to search
     * @param string $cityRef              City ID
     *
     * @return mixed
     */
    public function getCounterparties($counterpartyProperty = 'Recipient', $page = null, $findByString = null, $cityRef = null)
    {
        // Any param can be skipped
        $params = array();
        $params['CounterpartyProperty'] = $counterpartyProperty ? $counterpartyProperty : 'Recipient';
        $page and $params['Page'] = $page;
        $findByString and $params['FindByString'] = $findByString;
        $cityRef and $params['City'] = $cityRef;
        return $this->request('Counterparty', 'getCounterparties', $params);
    }

    /**
     * cloneLoyaltyCounterpartySender() function of model Counterparty
     * The counterparty will be not created immediately, you can wait a long time.
     *
     * @param string $cityRef City ID
     *
     * @return mixed
     */
    public function cloneLoyaltyCounterpartySender($cityRef)
    {
        return $this->request('Counterparty', 'cloneLoyaltyCounterpartySender', array('CityRef' => $cityRef));
    }

    /**
     * getCounterpartyContactPersons() function of model Counterparty.
     *
     * @param string $ref Counterparty ref
     *
     * @return mixed
     */
    public function getCounterpartyContactPersons($ref)
    {
        return $this->request('Counterparty', 'getCounterpartyContactPersons', array('Ref' => $ref));
    }

    /**
     * getCounterpartyAddresses() function of model Counterparty.
     *
     * @param string $ref  Counterparty ref
     * @param int    $page
     *
     * @return mixed
     */
    public function getCounterpartyAddresses($ref, $page = 0)
    {
        return $this->request('Counterparty', 'getCounterpartyAddresses', array('Ref' => $ref, 'Page' => $page));
    }

    /**
     * getCounterpartyOptions() function of model Counterparty.
     *
     * @param string $ref Counterparty ref
     *
     * @return mixed
     */
    public function getCounterpartyOptions($ref)
    {
        return $this->request('Counterparty', 'getCounterpartyOptions', array('Ref' => $ref));
    }

    /**
     * getCounterpartyByEDRPOU() function of model Counterparty.
     *
     * @param string $edrpou  EDRPOU code
     * @param string $cityRef City ID
     *
     * @return mixed
     */
    public function getCounterpartyByEDRPOU($edrpou, $cityRef)
    {
        return $this->request('Counterparty', 'getCounterpartyByEDRPOU', array('EDRPOU' => $edrpou, 'cityRef' => $cityRef));
    }

    /**
     * Get price of delivery between two cities.
     *
     * @param string $citySender    City ID
     * @param string $cityRecipient City ID
     * @param string $serviceType   (DoorsDoors|DoorsWarehouse|WarehouseWarehouse|WarehouseDoors)
     * @param float  $weight
     * @param float  $cost
     *
     * @return mixed
     */
    public function getDocumentPrice($citySender, $cityRecipient, $serviceType, $weight, $cost)
    {
        return $this->request('InternetDocument', 'getDocumentPrice', array(
            'CitySender' => $citySender,
            'CityRecipient' => $cityRecipient,
            'ServiceType' => $serviceType,
            'Weight' => $weight,
            'Cost' => $cost,
        ));
    }

    /**
     * Get approximately date of delivery between two cities.
     *
     * @param string $citySender    City ID
     * @param string $cityRecipient City ID
     * @param string $serviceType   (DoorsDoors|DoorsWarehouse|WarehouseWarehouse|WarehouseDoors)
     * @param string $dateTime      Date of shipping
     *
     * @return mixed
     */
    public function getDocumentDeliveryDate($citySender, $cityRecipient, $serviceType, $dateTime)
    {
        return $this->request('InternetDocument', 'getDocumentDeliveryDate', array(
            'CitySender' => $citySender,
            'CityRecipient' => $cityRecipient,
            'ServiceType' => $serviceType,
            'DateTime' => $dateTime,
        ));
    }

    /**
     * Get documents list.
     *
     * @param array $params List of params
     *                      Not required keys:
     *                      'Ref', 'IntDocNumber', 'InfoRegClientBarcodes', 'DeliveryDateTime', 'RecipientDateTime',
     *                      'CreateTime', 'SenderRef', 'RecipientRef', 'WeightFrom', 'WeightTo',
     *                      'CostFrom', 'CostTo', 'SeatsAmountFrom', 'SeatsAmountTo', 'CostOnSiteFrom',
     *                      'CostOnSiteTo', 'StateIds', 'ScanSheetRef', 'DateTime', 'DateTimeFrom',
     *                      'RecipientDateTime', 'isAfterpayment', 'Page', 'OrderField =>
     *                      [
     *                      IntDocNumber, DateTime, Weight, Cost, SeatsAmount, CostOnSite,
     *                      CreateTime, EstimatedDeliveryDate, StateId, InfoRegClientBarcodes, RecipientDateTime
     *                      ],
     *                      'OrderDirection' => [DESC, ASC], 'ScanSheetRef'
     *
     * @return mixed
     */
    public function getDocumentList($params = null)
    {
        return $this->request('InternetDocument', 'getDocumentList', $params ? $params : null);
    }

    /**
     * Get document info by ID.
     *
     * @param string $ref Document ID
     *
     * @return mixed
     */
    public function getDocument($ref)
    {
        return $this->request('InternetDocument', 'getDocument', array(
            'Ref' => $ref,
        ));
    }

    /**
     * Generetes report by Document refs.
     *
     * @param array $params Params like getDocumentList with requiered keys
     *                      'Type' => [xls, csv], 'DocumentRefs' => []
     *
     * @return mixed
     */
    public function generateReport($params)
    {
        return $this->request('InternetDocument', 'generateReport', $params);
    }

    /**
     * Check required fields for new InternetDocument and set defaults.
     *
     * @param array &$counterparty Recipient info array
     */
    protected function checkInternetDocumentRecipient(array &$counterparty)
    {
        // Check required fields
        if (!$counterparty['FirstName']) {
            throw new \Exception('FirstName is required filed for recipient');
        }
        // MiddleName realy is not required field, but manual says otherwise
        // if ( ! $counterparty['MiddleName'])
        // throw new \Exception('MiddleName is required filed for sender and recipient');
        if (!$counterparty['LastName']) {
            throw new \Exception('LastName is required filed for recipient');
        }
        if (!$counterparty['Phone']) {
            throw new \Exception('Phone is required filed for recipient');
        }
        if (!($counterparty['City'] or $counterparty['CityRef'])) {
            throw new \Exception('City is required filed for recipient');
        }
        if (!($counterparty['Region'] or $counterparty['CityRef'])) {
            throw new \Exception('Region is required filed for recipient');
        }

        // Set defaults
        if (empty($counterparty['CounterpartyType'])) {
            $counterparty['CounterpartyType'] = 'PrivatePerson';
        }
    }

    /**
     * Check required params for new InternetDocument and set defaults.
     *
     * @param array &$params
     */
    protected function checkInternetDocumentParams(array &$params)
    {
        if (!$params['Description']) {
            throw new \Exception('Description is required filed for new Internet document');
        }
        if (!$params['Weight']) {
            throw new \Exception('Weight is required filed for new Internet document');
        }
        if (!$params['Cost']) {
            throw new \Exception('Cost is required filed for new Internet document');
        }
        empty($params['DateTime']) and $params['DateTime'] = date('d.m.Y');
        empty($params['ServiceType']) and $params['ServiceType'] = 'WarehouseWarehouse';
        empty($params['PaymentMethod']) and $params['PaymentMethod'] = 'Cash';
        empty($params['PayerType']) and $params['PayerType'] = 'Recipient';
        empty($params['SeatsAmount']) and $params['SeatsAmount'] = '1';
        empty($params['CargoType']) and $params['CargoType'] = 'Cargo';
        if($params['CargoType'] != 'Documents') {
            empty($params['VolumeGeneral']) and $params['VolumeGeneral'] = '0.0004';
            empty($params['VolumeWeight']) and $params['VolumeWeight'] = $params['Weight'];
        }
    }

    /**
     * Create Internet Document by.
     *
     * @param array $sender    Sender info.
     *                         Required:
     *                         For existing sender:
     *                         'Description' => String (Full name i.e.), 'City' => String (City name)
     *                         For creating:
     *                         'FirstName' => String, 'MiddleName' => String,
     *                         'LastName' => String, 'Phone' => '000xxxxxxx', 'City' => String (City name), 'Region' => String (Region name),
     *                         'Warehouse' => String (Description from getWarehouses))
     * @param array $recipient Recipient info, same like $sender param
     * @param array $params    Additional params of Internet Document
     *                         Required:
     *                         'Description' => String, 'Weight' => Float, 'Cost' => Float
     *                         Recommended:
     *                         'VolumeGeneral' => Float (default = 0.004), 'SeatsAmount' => Int (default = 1),
     *                         'PayerType' => (Sender|Recipient - default), 'PaymentMethod' => (NonCash|Cash - default)
     *                         'ServiceType' => (DoorsDoors|DoorsWarehouse|WarehouseDoors|WarehouseWarehouse - default)
     *                         'CargoType' => String
     * @return mixed
     */
    public function newInternetDocument($sender, $recipient, $params)
    {
        // Check for required params and set defaults
        $this->checkInternetDocumentRecipient($recipient);
        $this->checkInternetDocumentParams($params);
        if (empty($sender['CitySender'])) {
            $senderCity = $this->getCity($sender['City'], $sender['Region'], $sender['Warehouse']);
            $sender['CitySender'] = $senderCity['data'][0]['Ref'];
        }
        $sender['CityRef'] = $sender['CitySender'];
        if (empty($sender['SenderAddress']) and $sender['CitySender'] and $sender['Warehouse']) {
            $senderWarehouse = $this->getWarehouse($sender['CitySender'], $sender['Warehouse']);
            $sender['SenderAddress'] = $senderWarehouse['data'][0]['Ref'];
        }
        if (empty($sender['Sender'])) {
            $sender['CounterpartyProperty'] = 'Sender';
            $fullName = trim($sender['LastName'].' '.$sender['FirstName'].' '.$sender['MiddleName']);
            // Set full name to Description if is not set
            if (empty($sender['Description'])) {
                $sender['Description'] = $fullName;
            }
            // Check for existing sender
            $senderCounterpartyExisting = $this->getCounterparties('Sender', 1, $fullName, $sender['CityRef']);
            // Copy user to the selected city if user doesn't exists there
            if (isset($senderCounterpartyExisting['data'][0]['Ref'])) {
                // Counterparty exists
                $sender['Sender'] = $senderCounterpartyExisting['data'][0]['Ref'];
                $contactSender = $this->getCounterpartyContactPersons($sender['Sender']);
                $sender['ContactSender'] = $contactSender['data'][0]['Ref'];
                $sender['SendersPhone'] = isset($sender['Phone']) ? $sender['Phone'] : $contactSender['data'][0]['Phones'];
            }
        }

        // Prepare recipient data
        $recipient['CounterpartyProperty'] = 'Recipient';
        $recipient['RecipientsPhone'] = $recipient['Phone'];
        if (empty($recipient['CityRecipient'])) {
            $recipientCity = $this->getCity($recipient['City'], $recipient['Region'], $recipient['Warehouse']);
            $recipient['CityRecipient'] = $recipientCity['data'][0]['Ref'];
        }
        $recipient['CityRef'] = $recipient['CityRecipient'];
        if (empty($recipient['RecipientAddress'])) {
            $recipientWarehouse = $this->getWarehouse($recipient['CityRecipient'], $recipient['Warehouse']);
            $recipient['RecipientAddress'] = $recipientWarehouse['data'][0]['Ref'];
        }
        if (empty($recipient['Recipient'])) {
            $recipientCounterparty = $this->model('Counterparty')->save($recipient);
            $recipient['Recipient'] = $recipientCounterparty['data'][0]['Ref'];
            $recipient['ContactRecipient'] = $recipientCounterparty['data'][0]['ContactPerson']['data'][0]['Ref'];
        }
        // Full params is merge of arrays $sender, $recipient, $params
        $paramsInternetDocument = array_merge($sender, $recipient, $params);
        // Creating new Internet Document
        return $this->model('InternetDocument')->save($paramsInternetDocument);
    }


}

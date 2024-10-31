<?php
/**
* Plugin Name:      PetPress
* Plugin URI:       https://www.airdriemedia.com/petpress
* Version:          1.7
* Description:      PetPress allows PetPoint users to create lists and detail pages for animals in their shelter(s). PetPress retrieves PetPoint data and displays it on your WordPress website. By using a shortcode, you can list animals in a shelter location by species, or you can show the details of an individual animal. Results pulled from PetPoint are cached in the local database for fastest performance.
* Author:           Airdrie Media
* Author URI:       https://www.airdriemedia.com
* License:          GPL v2 or later
* License URI:      https://www.gnu.org/licenses/gpl-2.0.html
*/

//error_reporting(E_ALL);

const petpress_kVersion = "1.7";
const petpress_kRefreshInterval = 30;
const petpress_kURLBASE = "https://ws.petango.com/webservices/wsadoption.asmx/";

function petpress_enqueue_stylesheet() {
    wp_enqueue_style( 'core', WP_PLUGIN_URL . '/petpress/includes/petpress_style.css', false, petpress_kVersion ); 
}

function petpress_scripts() {
    wp_enqueue_script('my-script', plugin_dir_url( __FILE__ ) . 'includes/petpress.js', array('jquery'), petpress_kVersion, true);
}
add_action('wp_enqueue_scripts', 'petpress_scripts');

add_action( 'wp_enqueue_scripts', 'petpress_enqueue_stylesheet', 10 );

// On hold for version 1.7
add_action('init', 'petpress_handle_request'); // manage detail page calls

class petpress_utilities_class{
    private $options;

    public function __construct()
    {
        $this->options = get_option( 'petpress_plugin_options' );
    }

    function getAuthKey(&$theKey){
    
        //$options = get_option( 'petpress_plugin_options' );
        if (!$this->options) {
            $theKey = "PetPress Message: Can't get plugin options - Have you gone to the Settings->PetPress page and configured the plugin yet? (LN#" . __LINE__ . ")";
            return false;
        }
        if (($this->options == true) && array_key_exists("auth_key",$this->options)) {
            if (strlen($this->options['auth_key']) > 5) {
                $theKey = $this->options['auth_key'];
                return true;
            }
        }
        else { // can't find auth_key -- error
            $theKey = "PetPress Message: Can't get the Authorization Key - Have you gone to the Settings->PetPress page and configured the plugin yet? (LN#" . __LINE__ . ")";
            return false;
        }
        return "";
    }

    function detailPageURL($idIN, $nameIN)
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $pageURI = $_SERVER['REQUEST_URI'];

        $parsed_url = parse_url($pageURI);
        $uri_without_querystring = $parsed_url['path'];

        $nameNoSpaces = str_replace(' ', '', $nameIN);
       // $content .= "<a href=" . $protocol . "://" . $host . $uri_without_querystring . "petpress/" . $critter->id . "/" . urlencode($nameNoSpaces) . ">";
       return $protocol . "://" . $host . $uri_without_querystring . "pp" . $idIN . "/" . urlencode($nameNoSpaces);
    }

    function optionChecked($theKey){
        if ($this->options){
            if (array_key_exists($theKey,$this->options)){
                if ($this->options[$theKey] == 1) { // checked
                    return true;
                }
            }
        }
        return false;
    }

    function longDate($dateIN)
    {
        $dateParts = explode("-", $dateIN);
        if (count($dateParts) <3 )
            return "unspecified date";
        return $dateParts[1] . "/" . $dateParts[2] . "/" . $dateParts[0];
    
    }

}

class petpress_animal_class{
    private $id;
    private $adoptionapplicationurl;
    private $age;
    private $agegroup;
    private $altered;
    private $arn;
    private $behaviorresult;
    private $behaviortestlist;
    private $breed;
    private $chipnumber;
    private $companyid;
    private $colorpattern;
    private $datasource;
    private $dateofbirth;
    private $daysin;
    private $featured;
    private $housetrained; // Yes, No, Unknown, Partially
    private $lastintakedate;
    private $livedwithanimals;
    private $livedwithanimaltypes;
    private $livedwithchildren;
    private $location;
    private $memo;
    private $name;
    private $nocats;
    private $nodogs;
    private $nokids;
    private $onhold;
    private $photo1;
    private $photo2;
    private $photo3;
    private $photocount;
    private $price;
    private $primarycolor;
    private $reasonforsurrender;
    private $secondarybreed;
    private $secondarycolor;
    private $sex;
    private $sitename;
    private $size;
    private $specialneeds;
    private $species;
    private $stage;
    private $sublocation;
    private $time;
    private $videoid;
    private $weight;

    function set_id($id) { $this->id = trim($id); }
    function get_id() { return $this->id; }

    function set_adoptionapplicationurl($input) { $this->adoptionapplicationurl = $input; }
    function get_adoptionapplicationurl() { return $this->adoptionapplicationurl; }

    function set_age($input) { $this->age = $input; }
    function get_age() { return $this->age; }

    function set_arn($input) { $this->arn = petpress_makeString($input); }
    function get_arn() { return $this->arn; }

    function formatAge() {
        $ageIN = $this->get_age();
        $yrs = floor($ageIN / 12);
        $mon = ($ageIN % 12);
        $strAge = "";
        if ($yrs > 0) { $strAge = $yrs . " year" ; }
        if ($yrs > 1) { $strAge .= "s" ;}
        if (($yrs > 0) && ($mon > 0)) { $strAge .= ", "; }
        if ($mon > 0) { $strAge .= $mon . " month"; }
        if ($mon > 1) { $strAge .= "s" ;}
        return ($strAge);
    }

    function approximateAge(){
        //      function gets age in months as input and outputs short string with approximate age in years
        $ageIN = $this->age;
        $yrs = floor($ageIN / 12);
        $mon = ($ageIN % 12);
        if ($mon >= 9 ) // at some point, round up
        {
            $yrs = $yrs + 1;
        }
        if ($ageIN == 0)
        {
            return (""); // age unknown
        }
        elseif ($yrs < 1)
        {
            return ("< 1 yr");
        }
        elseif ($yrs == 1)
        {
            return ("~ " . $yrs . " yr");
        }
        else
        {
            return ("~ " . $yrs . " yr");
        }
    }

    private function calculateDays($lidIN){
        $datetime1 = date_create(date('Y-m-d H:i:s'));
        $datetime2 = date_create($lidIN);  
        $interval = date_diff($datetime1, $datetime2);
        return $interval->format("%a");
    }

    function set_agegroup($input) { $this->agegroup = $input; }
    function get_agegroup() { return $this->agegroup; }

    function set_altered($input) { $this->altered = $input; }
    function get_altered() { return $this->altered; }

    function set_behaviorresult($input) { $this->behaviorresult = $input; }
    function get_behaviorresult() { return $this->behaviorresult; }

    function set_behaviortestlist($input) { $this->behaviortestlist = $input; }
    function get_behaviortestlist() { return $this->behaviortestlist; }

    function set_breed($breed) { $this->breed = $breed; }
    function get_breed() { return $this->breed; }

    function set_chipnumber($chipnumber) { $this->chipnumber = $chipnumber; }
    function get_chipnumber() { return $this->chipnumber; }

    function set_colorpattern($input) { $this->colorpattern = petpress_makeString($input); }
    function get_colorpattern() { return $this->colorpattern; }

    function set_companyid($companyID) { $this->companyid = $companyID; }
    function get_companyid() { return $this->companyid; }

    function set_datasource($input) { $this->datasource = $input; }
    function get_datasource() { return $this->datasource; }

    function set_dateofbirth($input) { $this->dateofbirth = $input; }
    function get_dateofbirth() { return $this->dateofbirth; }

    function set_daysin($daysin) {
        if (is_int($daysin) || is_string($daysin)){
            $this->daysin = $daysin;
        }
        else { // input is a date (?)
            $this->daysin = $this->calculateDays($daysin); 
        }
    }
    function get_daysin() { return $this->daysin; }

    function set_featured($input) { $this->featured = $input; }
    function get_featured() { return $this->featured; }

    function set_housetrained($input) { $this->housetrained = $input; }
    function get_housetrained() { return $this->housetrained; }

    function set_lastintakedate($input) { $this->lastintakedate = $input; }
    function get_lastintakedate() { return $this->lastintakedate; }

    function set_livedwithanimals($input) { $this->livedwithanimals = $input; }
    function get_livedwithanimals() { return $this->livedwithanimals; }

    function set_livedwithanimaltypes($input) { $this->livedwithanimaltypes = $input; }
    function get_livedwithanimaltypes() { return $this->livedwithanimaltypes; }

    function set_livedwithchildren($input) { $this->livedwithchildren = $input; }
    function get_livedwithchildren() { return $this->livedwithchildren; }

    function set_location($input) { $this->location = $input; }
    function get_location() { return $this->location; }

    function set_memo($memo) { $this->memo = $memo; }
    function get_memo() { return $this->memo; }

    function set_name($name) { $this->name = $name; }
    function get_name() { return $this->name; }

    function set_nokids($input) { $this->nokids = $input; }
    function get_nokids() { return $this->nokids; }

    function set_nocats($input) { $this->nocats = $input; }
    function get_nocats() { return $this->nocats; }

    function set_nodogs($input) { $this->nodogs = $input; }
    function get_nodogs() { return $this->nodogs; }

    function set_onhold($input) { $this->onhold = $input; }
    function get_onhold() { return $this->onhold; }

    function set_photo1($input) {
        if ($input == "https://g.petango.com/shared/Photo-Not-Available-cat.gif")
            {
                $input = plugins_url() . "/petpress/includes/images/airdriemedia_cat.jpg";
            }
        elseif ($input == "https://g.petango.com/shared/Photo-Not-Available-dog.gif")
            {
                $input = plugins_url() . "/petpress/includes/images/airdriemedia_dog.jpg";
            }

        elseif ($input == "https://g.petango.com/shared/Photo-Not-Available-other.gif")
            {
                $input = plugins_url() . "/petpress/includes/images/airdriemedia_other.jpg";
            }


        $this->photo1 = $input; 
    }
    function get_photo1() { return $this->photo1; }

    function set_photo2($input) { $this->photo2 = $input; }
    function get_photo2() { return $this->photo2; }

    function set_photo3($input) { $this->photo3 = $input; }
    function get_photo3() { return $this->photo3; }

    function set_photocount($input) { $this->photocount = $input; }
    function get_photocount() { return $this->photocount; }

    function set_price($input) { $this->price = $input; }
    function get_price() { return $this->price; }

    function set_primarycolor($input) { $this->primarycolor = petpress_makeString($input); }
    function get_primarycolor() { return $this->primarycolor; }

    function set_reasonforsurrender($input) { $this->reasonforsurrender = $input; }
    function get_reasonforsurrender() { return $this->reasonforsurrender; }

    function set_secondarybreed($input) { $this->secondarybreed = petpress_makeString($input); }
    function get_secondarybreed() { return $this->secondarybreed; }

    function set_secondarycolor($input) { $this->secondarycolor = petpress_makeString($input); }
    function get_secondarycolor() { return $this->secondarycolor; }

    function set_sex($input) { $this->sex = $input; }
    function get_sex() { return $this->sex; }

    function set_sitename($input) {
        $input = str_replace("Pennsylvania SPCA-", "", $input);
        $input = str_replace("Main Line Animal Rescue", "MLAR", $input);
        $this->sitename = $input; 
    }
    function get_sitename() { return $this->sitename; }

    function set_size($input) { $this->size = petpress_makeString($input); }
    function get_size() { return $this->size; }
    
    function set_specialneeds($input) { $this->specialneeds = $input; }
    function get_specialneeds() { return $this->specialneeds; }

    function set_species($species) { $this->species = $species; }
    function get_species() { return $this->species; }

    function set_stage($stage) { $this->stage = $stage; }
    function get_stage() { return $this->stage; }

    function set_sublocation($input) { $this->sublocation = $input; }
    function get_sublocation() { return $this->sublocation; }

    function set_time($input) { $this->time = $input; }
    function get_time() { return $this->time; }

    function set_videoid($input) { $this->videoid = $input; }
    function get_videoid() { return $this->videoid; }

    function set_weight($weight) { $this->weight = $weight; }
    function get_weight() { return $this->weight; }

    function get_shortWeight() {
        $INweight = $this->weight;
        //   "<!-- SHORTWEIGHT IS [" . $INweight . "]-->";
        if ($INweight == 0) {return "";}
        if (preg_replace('/\s+/', '', $INweight) =="") { return ""; }
            else {
        return strtok($INweight, " ") . " lb" ;}
    }

    function isInFoster()
    {
        if ((strpos(strtolower($this->location), "foster" )> 0) || (strpos(strtolower($this->sublocation), "foster" )> 0)){
            if ($this->location != "MLAR-Office Foster") {
                return true;
            }
        }
        if (($this->sitename == "Main Line Animal Rescue") || ($this->sitename == "MLAR")) {
            if ((substr($this->price, -3) == ".02") || (substr($this->price, -3) == ".03") || (substr($this->price, -3) == ".06") || (substr($this->price, -3) == ".07"))
            {
                return true;
            } 
        }
        return false;
    }

    function adoptionPending()
    {
        if (substr($this->stage, 0,7) == "Adopted") {
            return true;
        }
        if (($this->sitename == "Main Line Animal Rescue") || ($this->sitename == "MLAR")) {
            if ((substr($this->price, -3) == ".01") || (substr($this->price, -3) == ".03") || (substr($this->price, -3) == ".05") || (substr($this->price, -3) == ".07"))
            {
                return true;
            }
        }
        return false;
    }
}

    // $petpress_kAuthKey = "";
    //$petpress_kError ="";



function petpress_getDBTime(){
    global $wpdb;
    $query = "SELECT CURRENT_TIMESTAMP;";
    $theDBTime = $wpdb->get_var($query);
    return $theDBTime;
}

function petpress_makeString($strIN){
    if (is_string($strIN)) {
        return trim($strIN);
    }
    if (is_a($strIN, 'SimpleXMLElement')) {
        return (trim($strIN->__toString()));
    }
    return ("not a string");
}

function petpress_getOneAnimal($animalIDIN, $authKeyIN, $forceRefreshIN){
    $critter = new petpress_animal_class();
    //global $petpress_kAuthKey;

    // get from DB if possible
    global $wpdb;
    $table_name = $wpdb->prefix . "petpress_animals"; 
    $query = "SELECT * from " . $table_name . " where id='" . $animalIDIN ."'";
 
    $row = $wpdb->get_row($query);
    if (null === $row) { 
        //  "<h1>row wasn't found</h1>"; 
    }
    elseif ($forceRefreshIN === false) {
        $critter->set_id($animalIDIN);
        $critter->set_adoptionapplicationurl($row->adoptionapplicationurl);
        $critter->set_age($row->age);
        $critter->set_agegroup($row->agegroup);
        $critter->set_altered($row->altered);
        $critter->set_arn($row->arn);
        $critter->set_behaviorresult($row->behaviorresult);
        $critter->set_behaviortestlist($row->behaviortestlist);
        $critter->set_breed($row->breed);
        $critter->set_chipnumber($row->chipnumber);
        $critter->set_companyid($row->companyid);
        $critter->set_colorpattern($row->colorpattern);
        $critter->set_datasource("db");
        $critter->set_dateofbirth($row->dateofbirth);
        $critter->set_daysin($row->daysin);
        $critter->set_featured($row->featured);
        $critter->set_housetrained($row->housetrained);
        $critter->set_lastintakedate($row->lastintakedate);
        $critter->set_livedwithanimals($row->livedwithanimals);
        $critter->set_livedwithanimaltypes($row->livedwithanimaltypes);
        $critter->set_livedwithchildren($row->livedwithchildren);
        $critter->set_location($row->location);
        $critter->set_memo($row->memo);
        $critter->set_name($row->name);
        $critter->set_nocats($row->nocats);
        $critter->set_nodogs($row->nodogs);
        $critter->set_nokids($row->nokids);
        $critter->set_onhold($row->onhold);
        $critter->set_price($row->price);
        $critter->set_photo1($row->photo1);
        $critter->set_photo2($row->photo2);
        $critter->set_photo3($row->photo3);
        $critter->set_reasonforsurrender($row->reasonforsurrender);
        $critter->set_secondarybreed($row->secondarybreed);
        $critter->set_sex($row->sex);
        $critter->set_sitename($row->sitename);
        $critter->set_specialneeds($row->specialneeds);
        $critter->set_species($row->species);
        $critter->set_stage($row->stage);
        $critter->set_sublocation($row->sublocation);
        $critter->set_time($row->time);
        $critter->set_videoid($row->videoid);
        $critter->set_weight($row->weight);
        return $critter;
    }

    // create URL for getting adoptable details
    $urlWSComplete = petpress_kURLBASE . "AdoptableDetails?authKey=$authKeyIN";
    $urlWSComplete = "$urlWSComplete&animalID=$animalIDIN"; 
   
    $outputWS = wp_remote_get($urlWSComplete);

    if ( is_wp_error( $outputWS ) ) {
        throw new petpress_ShowStopper("Error getting single animal from ". $urlWSComplete ." (LN#" . __LINE__ . "). This may be a temporary connection issue.");
    }
    if ($outputWS["response"]["code"] == 500){throw new petpress_ShowStopper("The animal with that ID was not found. An error with the data service seems to have occurred. (LN#" . __LINE__ . ") 500"); }
    if ($outputWS["response"]["code"] == 404){return "404"; }

    if (is_string($outputWS["body"])){
        if (strpos($outputWS["body"], "502 Bad Gateway") !== false) {
            throw new petpress_ShowStopper("There is currently a problem with the PetPoint gateway (502 Bad Gateway) (LN#" . __LINE__ . "). This may be a temporary connection issue.");
        }
        if (strpos($outputWS["body"], "Resource not found") !== false) {
            return "Not found";
        }
    }

    $xmlWSdetail = simplexml_load_string($outputWS["body"]);
    //$testxmlWSdetail = simplexml_load_string($testoutputWS);


    //return $xmlWSdetail->AnimalName;
    $critter->set_id($animalIDIN);
    $critter->set_adoptionapplicationurl(petpress_makeString($xmlWSdetail->AdoptionApplicationUrl));
    $critter->set_age($xmlWSdetail->Age);
    $critter->set_agegroup(petpress_makeString($xmlWSdetail->AgeGroup));
    $critter->set_altered(petpress_makeString($xmlWSdetail->Altered));
    $critter->set_arn($xmlWSdetail->ARN);
    $critter->set_behaviorresult(petpress_makeString($xmlWSdetail->BehaviorResult));
    $critter->set_behaviortestlist(petpress_makeString($xmlWSdetail->BehaviorTestList));
    $critter->set_breed(petpress_unscrambleBreedName(petpress_makeString($xmlWSdetail->PrimaryBreed)));
    $critter->set_chipnumber(petpress_makeString($xmlWSdetail->ChipNumber));
    $critter->set_companyid(petpress_makeString($xmlWSdetail->CompanyID));
    $critter->set_colorpattern($xmlWSdetail->ColorPattern);
    $critter->set_datasource("pp");
    $critter->set_dateofbirth($xmlWSdetail->DateOfBirth);
    //$critter->set_daysin(petpress_calculateDays($xmlWSdetail->LastIntakeDate));
    $critter->set_daysin($xmlWSdetail->LastIntakeDate);
    if (strlen($xmlWSdetail->Dsc) > 5) {
        $critter->set_memo($xmlWSdetail->Dsc->__toString());
    }
    $critter->set_featured(petpress_makeString($xmlWSdetail->Featured));
    $critter->set_housetrained(petpress_makeString($xmlWSdetail->Housetrained));

    $critter->set_lastintakedate($xmlWSdetail->LastIntakeDate);
    $critter->set_livedwithanimals(petpress_makeString($xmlWSdetail->LivedWithAnimals));
    $critter->set_livedwithanimaltypes(petpress_makeString($xmlWSdetail->LivedWithAnimalTypes));
    $critter->set_livedwithchildren(petpress_makeString($xmlWSdetail->LivedWithChildren));

    $critter->set_location($xmlWSdetail->Location->__toString());
    $critter->set_name($xmlWSdetail->AnimalName->__toString());

    $critter->set_nocats(petpress_makeString($xmlWSdetail->NoCats));
    $critter->set_nodogs(petpress_makeString($xmlWSdetail->NoDogs));
    $critter->set_nokids(petpress_makeString($xmlWSdetail->NoKids));

    $critter->set_onhold(petpress_makeString($xmlWSdetail->OnHold));
    if (strlen($xmlWSdetail->Photo1->__toString()) > 5) {
        $critter->set_photo1(petpress_makeString($xmlWSdetail->Photo1));
    }
    else { $critter->set_photo1(""); }
    if (strlen($xmlWSdetail->Photo2) > 5) {
        $critter->set_photo2(petpress_makeString($xmlWSdetail->Photo2));
    }
    else { $critter->set_photo2(""); }
    if (strlen($xmlWSdetail->Photo3) > 5) {
        $critter->set_photo3(petpress_makeString($xmlWSdetail->Photo3));
    }
    else { $critter->set_photo3(""); }
    if (strlen($xmlWSdetail->Price) > 1) {
        $critter->set_price(petpress_makeString($xmlWSdetail->Price));
    }

    $critter->set_primarycolor($xmlWSdetail->PrimaryColor);

    $critter->set_reasonforsurrender(petpress_makeString($xmlWSdetail->ReasonForSurrender));
    $critter->set_secondarybreed(petpress_unscrambleBreedName($xmlWSdetail->SecondaryBreed));

    $critter->set_secondarycolor($xmlWSdetail->SecondaryColor);

    $critter->set_sex(petpress_makeString($xmlWSdetail->Sex));

    if (strlen($xmlWSdetail->Site) > 1) {
        $critter->set_sitename($xmlWSdetail->Site->__toString());
    }
    $critter->set_size($xmlWSdetail->Size);
    
    $critter->set_specialneeds(petpress_makeString($xmlWSdetail->SpecialNeeds));
    $critter->set_species(petpress_makeString($xmlWSdetail->Species));
    $critter->set_stage(petpress_makeString($xmlWSdetail->Stage));
    if (strlen($xmlWSdetail->VideoID) > 5) {
        $critter->set_videoid(petpress_makeString($xmlWSdetail->VideoID));
    }
    $critter->set_sublocation(petpress_makeString($xmlWSdetail->Sublocation));
    $critter->set_time("now");
    $weight =  (preg_replace('/[^0-9]/', '', $xmlWSdetail->BodyWeight)) ; // remove "pounds"
    $critter->set_weight($weight);

    petpress_writeOneAnimal($xmlWSdetail->AnimalType, $critter);
    return $critter;
}

function petpress_writeOneAnimal($speciesIN, $critterIN)
{

    $weight = 0;
    if ((strlen($critterIN->get_weight() <= 3)))
    {
        $weight=0;
    }
    else
    {
        $weight = $critterIN->get_weight();
    }
    global $wpdb;

    $theCurDate = petpress_getDBTime();

    $table_name = $wpdb->prefix . "petpress_animals"; 

    $dateofbirth = date("Y-m-d", strtotime($critterIN->get_dateofbirth()));
    $lastintakedate = date("Y-m-d", strtotime($critterIN->get_lastintakedate()));

    $rc = $wpdb->replace( 
    $table_name, 
        array( 
            'time' => $theCurDate,
            'id' => $critterIN->get_id() + 0,
            'adoptionapplicationurl' => $critterIN->get_adoptionapplicationurl(),
            'age' => (int)$critterIN->get_age(),
            'agegroup' =>  $critterIN->get_agegroup(),
            'altered' =>  $critterIN->get_altered(),
            'arn' =>  $critterIN->get_arn(),
            'behaviorresult' => $critterIN->get_behaviorresult(),
            'behaviortestlist' =>  $critterIN->get_behaviortestlist(),
            'breed' => petpress_unscrambleBreedName($critterIN->get_breed()) ,
            'chipnumber' =>   $critterIN->get_chipnumber(),
            'companyid' =>  $critterIN->get_companyid() ,  
            'colorpattern' =>  $critterIN->get_colorpattern() , 
            'dateofbirth' =>  $dateofbirth  ,
            'daysin' => (int)$critterIN->get_daysin(),
            'featured' => $critterIN->get_featured(),
            'housetrained' => $critterIN->get_housetrained(),
            'lastintakedate' => $lastintakedate ,
            'livedwithanimals' => $critterIN->get_livedwithanimals() ,
            'livedwithanimaltypes' => $critterIN->get_livedwithanimaltypes() ,
            'livedwithchildren' => $critterIN->get_livedwithchildren() ,
            'location' =>  $critterIN->get_location(),
            'memo' => $critterIN->get_memo(),
            'name' =>  $critterIN->get_name(),
            'nocats' =>  $critterIN->get_nocats(),
            'nodogs' =>  $critterIN->get_nodogs(),
            'nokids' =>  $critterIN->get_nokids(),
            'onhold' =>  $critterIN->get_onhold(),
            'price' =>  $critterIN->get_price(),
            'primarycolor' => $critterIN->get_primarycolor(),
            'photo1' =>  $critterIN->get_photo1(),
            'photo2' =>  $critterIN->get_photo2(),
            'photo3' =>  $critterIN->get_photo3(),
            'reasonforsurrender' =>  $critterIN->get_reasonforsurrender(),
            'secondarybreed' =>  petpress_unscrambleBreedName($critterIN->get_secondarybreed()),
            'secondarycolor' => $critterIN->get_secondarycolor(),
            'sex' =>  $critterIN->get_sex(),
            'sitename' =>  $critterIN->get_sitename(),
            'size' =>  $critterIN->get_size(),
            'specialneeds' => $critterIN->get_specialneeds(),
            'species' => $critterIN->get_species(),
            'stage' =>  $critterIN->get_stage(),
            'sublocation' => $critterIN->get_sublocation(),
            'videoid' => $critterIN->get_videoid(),
            'weight' => (int)$weight
        )
    )  ;

}



function petpress_getFoundRoster($speciesIN, $authKeyIN, $forceRefreshIN)
{
    $utils = new petpress_utilities_class();

    $urlWSCompleteOUT   = "";
    $sex                = "A";  // Gender
    $ageGroup           = "All";// Age Group
    $orderBy            = "ID"; // Order By
    $searchOption       = "0";  // Search Option
        
    $urlWSCompleteOUT  = petpress_kURLBASE . "foundSearch?AuthKey=$authKeyIN";  //Initial URL build
    $urlWSCompleteOUT  = "$urlWSCompleteOUT&speciesID=$speciesIN" ;             //Add Species ID
    $urlWSCompleteOUT  = "$urlWSCompleteOUT&sex=$sex" ;                         //Add Gender
    $urlWSCompleteOUT  = "$urlWSCompleteOUT&ageGroup=$ageGroup" ;               //Add Age Group
    $urlWSCompleteOUT  = "$urlWSCompleteOUT&orderBy=$orderBy" ;                 //Add Order By
    $urlWSCompleteOUT  = "$urlWSCompleteOUT&searchOption=$searchOption" ;       //Add Search Option        
    
    $outputWS = wp_remote_get($urlWSCompleteOUT);
    if ($outputWS == false) {
        return "No data delivered."; 
    }
    elseif (str_contains($outputWS["body"], "Access denied")){
        return "Access denied by PetPoint. Please check your Authorization Key in settings and that web services are enabled by PetPoint for your organization.";
    }
    else
    {
        if (is_string($outputWS["body"])){
            if (strpos($outputWS["body"], "502 Bad Gateway") !== false){
                throw new petpress_ShowStopper("There is currently a problem with the PetPoint gateway (502 Bad Gateway) (LN#" . __LINE__ . "). This may be a temporary connection issue.");       
            }
        }
        $xmlWS = simplexml_load_string($outputWS["body"]);
    /* $testXML = '<?xml version="1.0" encoding="utf-8"?><ArrayOfXmlNode xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://www.petango.com/">
     <XmlNode>
         <an xmlns="">
             <ID>49921482</ID>
             <Name>Horton</Name>
             <Species>Dog</Species>
             <PrimaryBreed>Terrier, Pit Bull</PrimaryBreed>
             <SecondaryBreed>Mix</SecondaryBreed>
             <Age>43</Age>
             <Sex>M</Sex>
             <FoundDate>2022-04-06</FoundDate>
             <FoundAddress>PSPCA</FoundAddress>
             <Type>Animal in Custody</Type>
             <Location>Greenhouse</Location>
             <Stage>Adoptable</Stage>
             <Photo>https://g.petango.com/photos/...fe730.jpg</Photo>
             <PrimaryColor>White</PrimaryColor>
             <SecondaryColor>Brindle</SecondaryColor>
             <City>Philadelphia</City>
             <State>PA</State>
             <SpayedNeutered>Yes</SpayedNeutered>
             <Jurisdiction>Philadelphia</Jurisdiction>
             <ARN> </ARN>
         </an>
     </XmlNode>
 </ArrayOfXmlNode>';
 */
   //  $xmlWS = simplexml_load_string($testXML);
        $animals = array();

        $htmlOut = "<div id='pp_foundlist'>";
        foreach ($xmlWS->children() as $children) {
            if (strlen(preg_replace('/[^A-Za-z0-9\-]/', '', $children->an->ID)) > 1) {  
                $htmlOut .= "\n<div id='pp_found_" . $children->an->ID . "' class='pp_found_item'>";
                $htmlOut .= "<div class='pp_imageframe pp_found_pic'><img class='pp_fimg pp_heroimage' src='" . $children->an->Photo . "'></div>";
                $htmlOut .= "<div class='pp_found_data'>";
                $htmlOut .= "<div class='pp_fname'>" . $children->an->Species . " known as " . $children->an->Name . "</div>";
                $htmlOut .= "<div class='pp_fstats'>" ;
                if ($children->an->Age > 0) {
                    $htmlOut .= $children->an->Age . " months old ";
//                    $htmlOut .= $children->an->approximateAge() . " old ";
                }
                if ($children->an->Sex == "M"){
                    $htmlOut .= "male ";
                }
                elseif ($children->an->Sex == "F"){
                    $htmlOut .= "female ";
                }
                $htmlOut .=  petpress_unscrambleBreedName($children->an->PrimaryBreed) ;
                $htmlOut .= "</div>"; 
                $htmlOut .= "<div class='pp_details xpp_listitem'>";
                $htmlOut .= "Found on " . $utils->longDate($children->an->FoundDate) . "<br>" . $children->an->FoundAddress ;
                $htmlOut .= "<br>";
                if (strlen(preg_replace('/[^A-Za-z0-9\-]/', '', $children->an->City)) > 1)
                //  $htmlOut .= "[strlen is " . strlen(preg_replace('/[^A-Za-z0-9\-]/', '', $children->an->City)) . "]";
                    $htmlOut .= $children->an->City . ", ";
                if (strlen($children->an->State) > 1)
                    $htmlOut .= $children->an->State;
                $htmlOut  .= "<br>";
                
                
                /*
                //$htmlOut .= $children->an->SecondaryBreed . "</td><td>";
                $htmlOut .= $children->an->Type . "</td><td>";
                $htmlOut .= $children->an->Location . "</td><td>";
                $htmlOut .= $children->an->Stage . "</td><td>";
                $htmlOut .= $children->an->Stage . "</td><td>";
                $htmlOut .= $children->an->PrimaryColor . "</td><td>";
                $htmlOut .= $children->an->SecondaryColor . "</td><td>";
                $htmlOut .= $children->an->SpayedNeutered . "</td><td>";
                $htmlOut .= $children->an->Jurisdiction . "</td><td>";            
                $htmlOut .= $children->an->ARN . "</td><td>";
    */
                $htmlOut .= "</div></div></div>";
            }
        }

        $htmlOut .= "\n</div>";
        return ($htmlOut);

        
    }
 

if (isset($roster)){
    return $roster; 
}
else {
    return "Found animal routine complete";
} 
}


function petpress_getRoster($speciesIN, $siteIN, $authKeyIN, $forceRefreshIN)
{
    
    GLOBAL $wpdb;
    //    if ( is_admin()) return "";

    $speciesIN = petpress_getSpeciesfromSpeciesID($speciesIN);

    if (!petpress_cacheNeedsRefresh($siteIN,$speciesIN) && ($forceRefreshIN ===false)){
    //if ($forceRefreshIN ===false){
        $table_name = $wpdb->prefix . "petpress_sites";

        $query = " SELECT roster FROM $table_name WHERE site = '$siteIN' AND species = '$speciesIN'";

        $dbRoster = $wpdb->get_var($query);

        $rosterIDs = unserialize($dbRoster);

        $rosterIDlist = "";

        if(is_array($rosterIDs)){
            foreach ($rosterIDs as $oneID){
                $searchResult =  petpress_getOneAnimal($oneID, $authKeyIN, $forceRefreshIN);
                if (is_string($searchResult)){
                    // couldn't find the animal 
                    $test = true;
                }
                else{
                $roster[] = $searchResult;
                }
            }
        }
    }
    else // refresh the cache
    {
        $urlWSComplete  = petpress_createAdoptableSearch($authKeyIN,$siteIN, $speciesIN);

        $outputWS = wp_remote_get($urlWSComplete);
        if ($outputWS == false) {
            return "No data delivered."; 
        }
        elseif (str_contains($outputWS["body"], "Access denied")){ // TODO: may throw error - Fatal error: Uncaught Error: Cannot use object of type WP_Error as array 
            return "Access denied by PetPoint. Please check your Authorization Key in settings and that web services are enabled by PetPoint for your organization.";
        }
        else
        {
            if (is_string($outputWS["body"])){
                if (strpos($outputWS["body"], "502 Bad Gateway") !== false){
                    throw new petpress_ShowStopper("There is currently a problem with the PetPoint gateway (502 Bad Gateway) (LN#" . __LINE__ . "). This may be a temporary connection issue.");       
                }
            }
            $xmlWS = simplexml_load_string($outputWS["body"]);
            $animals = array();

            foreach ($xmlWS->children() as $children) {
                $theID = $children->adoptableSearch->ID;
                if (!is_null($theID)){
                $searchResult  = petpress_getOneAnimal($theID, $authKeyIN, $forceRefreshIN);
                if (is_string($searchResult)){
                    // couldn't find the animal 
                    $test = "true";
                }
                else{
                $roster[] = $searchResult;
                }
                $rosterIDs[] = (string)$theID;
                }
            }
        }
        
        // loaded all the animals, reset the cache timeout.

        $table_name = $wpdb->prefix . "petpress_sites";
        $query = "SELECT id from " . $table_name . " where site='" . $siteIN ."' and species='" . $speciesIN . "'";
        $result = $wpdb->get_var($query);

        if($result != NULL){
            $wpdb->update($table_name, array('time' => petpress_getDBTime(),'roster' => serialize($rosterIDs)), array('id' => $result));
        }
        else{ // didn't find record, insert one

                // 'time' => current_time( 'mysql' ),
            if (isset($rosterIDs)){
                $rc = $wpdb->insert( $table_name, 
                    array( 
                        'time' => petpress_getDBTime(), 
                        'site' => $siteIN, 
                        'species' => $speciesIN,
                        'roster' => serialize($rosterIDs)
                    )
                );
            }
        }
    
    }

// Remove animals who are "on hold" if applicable

    if (isset($roster)){
        $options = get_option( 'petpress_plugin_options' );
        if ($options){
            if (array_key_exists('hideonhold',$options)) {
                if ($options["hideonhold"] == 1);{
                    // loop through roster, removing items that have "on hold set"
                    foreach ($roster as $key => $critter)
                    {
                        if ($critter->get_onhold() == "Yes") {
                            unset($roster[$key]);
                        }
                    }
                }        
            }
        }
    }

    if (isset($roster)){
        return $roster; 
    }
    else {
        return "No animals found for this species at this location.";
    }
}


if (!function_exists('str_contains')) {
    function str_contains (string $haystack, string $needle)
    {
        return empty($needle) || strpos($haystack, $needle) !== false;
    }
}


function petpress_cacheNeedsRefresh($siteIN,$speciesIN){
    // used for rosters
    global $wpdb;
    $table_name = $wpdb->prefix . "petpress_sites"; 

    $query = "SELECT time from " . $table_name . " where site='" . $siteIN . "'  and species='" . $speciesIN ."'";
    $result = $wpdb->get_var($query);

    if($result != NULL)
    {
        $theCurDate = petpress_getDBTime();

        $resultdate = new DateTime($result);
        $nowdate = new DateTime($theCurDate);

        $cacheAge =  $resultdate->diff($nowdate);
        $theDiff = ($cacheAge->m * 30 * 60 * 24) + ($cacheAge->d * 60 * 24) + ($cacheAge->h * 60) + ($cacheAge->i);

    //    $options = get_option( 'petpress_plugin_options' );
        if ($theDiff <= petpress_kRefreshInterval) 
        {
            return false;
        }
    }
    return true;
}




/*
function petpress_formatAge($ageIN) {
    $yrs = floor($ageIN / 12);
    $mon = ($ageIN % 12);
    $strAge = "";
    if ($yrs > 0) { $strAge = $yrs . " year" ; }
    if ($yrs > 1) { $strAge .= "s" ;}
    if (($yrs > 0) && ($mon > 0)) { $strAge .= ", "; }
    if ($mon > 0) { $strAge .= $mon . " month"; }
    if ($mon > 1) { $strAge .= "s" ;}
    return ($strAge);
}
*/


function petpress_unscrambleBreedName($breedIN){
    
    $outString = "";

    if (strpos($breedIN, ",") === false)
    {
        return $breedIN; // no commas found, returning the string as-is
    }
    
    $words = explode ("," , $breedIN);
    
    $index = count($words);
    
    // "<!-- descrambling " . $breedIN . "-->";
    
    while($index) {
    ///          ("<!--" . trim($words[--$index]) . "-->");
        $outString .= trim($words[--$index]) . " ";
    }
    
    if (strpos($outString, 'Mixed Breed') !== false)
    {
        $outString = strtok($outString, " ") . " Mixed Breed";
    }

    //$outString = strtolower($outString);
    //$outString = preg_replace('/american/', 'American', $outString);

    return trim($outString);

}

function petpress_comparedaysin($a, $b) {
    return $b->get_daysin() - $a->get_daysin(); // longest to shortest
}

function petpress_compareage($a, $b) {
    //return $a->age - $b->age; // youngest to oldest
    return $b->get_age() - $a->get_age(); // oldest to youngest
}

function petpress_compareweight($a, $b) {
    //return strcmp($a->weight, $b->weight);
    return $a->get_weight() - $b->get_weight();
}

function petpress_comparename($a, $b) {
    return strcmp($a->get_name(), $b->get_name());
    //return $a->age - $b->age;
}

// PetPoint Functions

function petpress_getSpeciesfromSpeciesID($speciesIDIN) {
    switch ($speciesIDIN) {
        case "0":
            return "Animal";
            break;
        case "1":
            return "Dog"; break;
        case "2":
            return "Cat"; break;
        case "3":
            return "Rabbit"; break;
        case "4":
            return "Horse"; break;
        case "5":
            return "Small and Furry"; break;
        case "6":
            return "Pig"; break;
        case "7":
            return "Reptile"; break;
        case "8":
            return "Bird"; break;
        case "9":
            return "Barnyard Animal"; break;
        case "1003":
            return "Other Animal"; break;
        default:
            return $speciesIDIN;
    }
}

function petpress_getSpeciesIDfromSpecies($speciesIN) {
    switch ($speciesIN) {
        case "Dog":
            return "1"; break;
        case "Cat":
            return "2"; break;
        case "Rabbit":
            return "3"; break;
        case "Horse":
            return "4"; break;
        case "Small and Furry":
            return "5"; break;
        case "Pig":
            return "6"; break;
        case "Reptile":
            return "7"; break;
        case "Bird":
            return "8"; break;
        case "Barnyard Animal":
            return "9"; break;
        case "Other Animal":
            return "1003"; break;
        default:
            return $speciesIN;
    }
}

function petpress_createAdoptableSearch($urlWSAuthKeyIN,$siteIN, $speciesIN) {
    $urlWSCompleteOUT = "";
    
    $speciesID = petpress_getSpeciesIDfromSpecies($speciesIN);

  //  $speciesID = $speciesIDIN; // 1= dogs, 2=cats
    $sex = "A";
    $ageGroup = "All";
    $location = "";
    $site = $siteIN;
    $stageID = ""; // stage id for max apps is 50936
    $onHold = "A";
    $orderBy = "Name";
    $primaryBreed = "All";
    $secondaryBreed = "All";
    $specialNeeds ="A";
    $noDogs       = "A";
    $noCats       = "A";
    $noKids     ="A";
    
    
    
    //$urlWSCompleteOUT = $urlWSBaseIN . "AdoptableSearchWithStage?authKey=$urlWSAuthKeyIN";
    
    $urlWSCompleteOUT = petpress_kURLBASE . "AdoptableSearch?authKey=$urlWSAuthKeyIN";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&speciesID=$speciesID";
    
    $urlWSCompleteOUT = "$urlWSCompleteOUT&sex=$sex";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&ageGroup=$ageGroup";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&location=$location";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&site=$site";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&stageID=$stageID";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&onHold=$onHold";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&orderBy=$orderBy";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&primaryBreed=$primaryBreed";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&secondaryBreed=$secondaryBreed";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&specialNeeds=$specialNeeds";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&noDogs=$noDogs";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&noCats=$noCats";
    $urlWSCompleteOUT = "$urlWSCompleteOUT&noKids=$noKids";
    
    return $urlWSCompleteOUT;
    
}


add_action( 'admin_menu', 'petpress_add_settings_page' );

add_action( 'petpress_cron_dog_data_load', 'petpress_precache_dog' );
add_action( 'petpress_cron_cat_data_load', 'petpress_precache_cat' );
add_action( 'petpress_cron_other_data_load', 'petpress_precache_other' );

function PetPress_activate(){
    petpress_createDatabaseTables();
    petpress_createcronjob();
}

function PetPress_deactivate(){
    global $wpdb;

    $timestamp = wp_next_scheduled( 'petpress_cron_dog_data_load' );
    wp_unschedule_event( $timestamp, 'petpress_cron_dog_data_load' );

    $timestamp = wp_next_scheduled( 'petpress_cron_cat_data_load' );
    wp_unschedule_event( $timestamp, 'petpress_cron_cat_data_load' );

    $timestamp = wp_next_scheduled( 'petpress_cron_other_data_load' );
    wp_unschedule_event( $timestamp, 'petpress_cron_other_data_load' );

    $table_name = $wpdb->prefix . "petpress_refresh"; 
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

    $table_name = $wpdb->prefix . "petpress_animals"; 
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

    $table_name = $wpdb->prefix . "petpress_sites"; 
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

    $table_name = $wpdb->prefix . "petpress_strays"; 
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

    //    $timestamp = wp_next_scheduled( 'petpress_cron_hook' );
    //    wp_unschedule_event( $timestamp, 'petpress_cron_hook' );
}

function PetPress_uninstall(){
    /* uninstall code */
}



add_filter( 'cron_schedules', 'petpress_add_everytwohour_schedule' );
add_filter( 'cron_schedules', 'petpress_add_everyhalfhour_schedule' );

function petpress_add_everytwohour_schedule( $schedules ) {
  $schedules['twohourly'] = array(
    'interval' => 1 * 2 * 60 * 60, //7 days * 24 hours * 60 minutes * 60 seconds
    'display' => __( 'Every Two Hours', )
  );
  return $schedules;
}

function petpress_add_everyhalfhour_schedule( $schedules ) {
  $schedules['halfhourly'] = array(
    'interval' => 1 * 1 * 30 * 60, //7 days * 24 hours * 60 minutes * 60 seconds
    'display' => __( 'Every Half Hour', )
  );
  return $schedules;
}

function petpress_createcronjob(){
    if ( ! wp_next_scheduled( 'petpress_cron_dog_data_load' ) ) {
        if (! wp_schedule_event( time(), 'halfhourly', 'petpress_cron_dog_data_load' )){
            // value was "hourly"
            $error = 'true';
        };
    }
    if ( ! wp_next_scheduled( 'petpress_cron_cat_data_load' ) ) {
        if (! wp_schedule_event( time() + 2000, 'halfhourly', 'petpress_cron_cat_data_load' )){
            $error = 'true';
        };
    }
    if ( ! wp_next_scheduled( 'petpress_cron_other_data_load' ) ) {
        if (! wp_schedule_event( time() + 1000, 'halfhourly', 'petpress_cron_other_data_load' )){
            $error = 'true';
        };
    }
}

function petpress_precache_dog(){
    $utils = new petpress_utilities_class();
    if($utils->getAuthKey($authKey) == true)
    {
    if (is_string($authKey)){
        if ($authKey !== ""){
            $theRoster = petpress_getRoster("1", "0", $authKey, true);
        }
    }
}   
}

function petpress_precache_cat(){
    $utils = new petpress_utilities_class();
    if($utils->getAuthKey($authKey) == true)
    {
    if (is_string($authKey)){
        if ($authKey !== ""){
            $theRoster = petpress_getRoster("2", "0", $authKey, true);
        }
    }
}
}

function petpress_precache_other(){
    $utils = new petpress_utilities_class();
    global $wpdb;

    $table_name = $wpdb->prefix . "petpress_sites"; 
    $query = "DELETE FROM $table_name WHERE time < DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    $result = $wpdb->query($query);

    $table_name = $wpdb->prefix . "petpress_animals";
    $query = "DELETE FROM $table_name WHERE time < DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    $result = $wpdb->query($query);

    if ($utils->getAuthKey($authKey)){
        if (is_string($authKey)){
            if ($authKey !== ""){
                $theRoster = petpress_getRoster("1003", "0", $authKey, true);
            }
        }
    }
}

function petpress_add_settings_page() {
    add_options_page( 'PetPress plugin page', 'PetPress', 'manage_options', 'petpress-plugin', 'petpress_render_plugin_settings_page' );
}

function petpress_createDatabaseTables(){
    global $wpdb;
 
    $table_name = $wpdb->prefix . "petpress_animals"; 
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
            id int NOT NULL ,
            time datetime default CURRENT_TIMESTAMP NOT NULL ,
            sitename tinytext NULL ,
            species tinytext NOT NULL ,
            name tinytext NOT NULL ,
            adoptionapplicationurl tinytext NULL ,
            age tinytext NULL ,
            agegroup tinytext NULL ,
            altered tinytext NULL ,
            animalname tinytext NULL ,
            animaltype tinytext NULL ,
            arn tinytext NULL ,
            bannerurl tinytext NULL ,
            behaviorresult text NULL ,
            behaviortestlist text NULL ,
            breed tinytext NULL ,
            buddyid smallint NULL ,
            chipnumber tinytext NULL ,
            colorpattern text NULL ,
            companyid text NULL ,
            dateofbirth datetime NULL ,
            daysin smallint NULL,
            declawed tinytext NULL ,
            featured tinytext NULL ,
            housetrained tinytext NULL ,
            lastintakedate datetime NULL ,
            livedwithanimals tinytext NULL ,
            livedwithanimaltypes tinytext NULL ,
            livedwithchildren tinytext NULL ,
            location tinytext NULL ,
            memolist text NULL ,
            memo text NULL ,
            nocats tinytext NULL ,
            nodogs tinytext NULL ,
            nokids tinytext NULL ,
            onhold tinytext NULL ,
            photo tinytext NULL ,
            photo1 tinytext NULL ,
            photo2 tinytext NULL ,
            photo3 tinytext NULL ,
            prevenvironment tinytext NULL ,
            price tinytext NULL ,
            primarycolor tinytext NULL ,
            reasonforsurrender tinytext NULL ,
            secondarybreed tinytext NULL ,
            secondarycolor tinytext NULL ,
            sex tinytext NULL ,
            size tinytext NULL ,
            sn tinytext NULL ,
            specialneeds tinytext NULL ,
            stage tinytext NULL ,
            sublocation tinytext NULL ,
            timeinformerhome tinytext NULL ,
            videoid tinytext NULL ,
            weight smallint NULL ,
            wildlifeintakecause tinytext NULL ,
            wildlifeintakeinjury tinytext NULL ,
            PRIMARY KEY  (id)
      ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    $table_name = $wpdb->prefix . "petpress_sites"; 
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        site tinytext NOT NULL,
        species tinytext NOT NULL,
        roster text NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
    dbDelta( $sql );

    $table_name = $wpdb->prefix . "petpress_strays"; 
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        companyid tinytext NOT NULL,
        name tinytext NULL,
        species tinytext NOT NULL,
        primarybreed tinytext NULL,
        secondarybreed tinytext NULL,
        age tinytext NULL,
        sex tinytext NULL,
        founddate datetime NOT NULL,
        foundaddress tinytext NULL,
        type tinytext NULL,
        location tinytext NULL,
        stage tinytext NULL,
        photo tinytext NULL,
        primarycolor tinytext NULL,
        secondarycolor tinytext NULL,
        city tinytext NULL,
        state tinytext NULL,
        spayedneutered tinytext NULL,
        jurisdiction tinytext NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
    dbDelta( $sql );
}


function petpress_render_plugin_settings_page() {
    ?>
    <div style="margin-right:20px">
    <div style="float:right"><a href="https://www.airdriemedia.com"><img src="<?= WP_PLUGIN_URL ?>/petpress/includes/images/AirdrieMedia50.png"></a></div>
    <h1>PetPress Plugin Settings</h1>
    <p>PetPress is a product of Airdrie Media. For more information about this plugin, including instructions on its use, 
        please visit <a href="https://www.airdriemedia.com/petpress">AirdrieMedia.com/PetPress</a>.</p>
        <form action="options.php" method="post">
        <?php 
        settings_fields( 'petpress_plugin_options' );
        do_settings_sections( 'petpress_plugin' ); ?>
         <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save Settings' ); ?>" />
    </form>
    <hr>
    <h2>Important Message (well, important to me!)</h2>
    <p>I sincerely hope that this plugin makes it possible for you to keep your website development and maintenance costs down and by doing so enable you to
        find more homes for more animals. Please know that PetPress was created without sponsorship (funding) from any company or organization.
        If you like PetPress, please consider <a href="https://www.paypal.com/paypalme/airdriemedia">making a donation</a> to support its further development. Thank you!</p>

    <h2>Using the Shortcode</h2>
    <p>The shortcode PETPRESS will render the PetPoint information. Here are the parameters:</p>
    <ul>
        <li><b>site</b>: This is the site ID for the site for which you want to show the roster. If your organization has only one site, you may omit this parameter.
        <li><b>species</b> : The species number for a list of animals. Valid values are 1 for dogs, 2 for cats, 1003 for other animals.</li>
        <li><b>sort</b> : Sort order. Valid values are "age", "name", or "weight". Default value is "name". If "id" is specified, the sort parameter does nothing (because there is only one animal).</li>
        <li><b>heading</b> : The heading you want displayed on your list page.</li>
        <li><b>id</b> : The PetPoint ID of a single animal. This will cause a detail page to be rendered with information on that specific animal. In practice, it would probably be preferable to pass the ID in the URL if you are generating links outside of the plugin. See the <a href="https://www.airdriemedia.com/petpress">documentation</a> for details.</li>
    </ul>
    <p>Sample shortcode: <b>[PETPRESS species="1"]</b> Shows all dogs (species 1) in your organization.</p>
        </div>

        <div class="postbox" style=" margin:20px 20px 30px 0px; background-color:#ff9">
        <div class="inside"><h3>BETA FEATURES</h3>
        <p>This version contains several features that are in "Beta" meaning that they can be used but are not fully-developed features of the site. These features may or may not be developed further (depending on interest) and their function many change before they are finalized.<p>
        <h4>Found Animals Report</h4>
            <p>I have received several requests to support the listing of found animals. This is now available as "beta" feature, meaning that it is in its most basic form and subject to change. See the <a href="https://www.airdriemedia.com/petpress">documentation</a> for information on the found animals report. This report may or may not evolve beyond the beta stage depending on user feedback on this feature.</p>
        <h4>Cache Reset</h4>
            <p>The cache (roster of animals and animal details) are automatically refreshed periodically (30 minute intervals as of version 1.4.3). There is a feature to allow a user (with a code) to reset the cache immediately, which might be handy if an animal needs to be posted or removed in a hurry. See the <a href="https://www.airdriemedia.com/petpress">documentation</a> for details.</p>
        <h4>Volunteer Report</h4>
            <p>This feature would allow the setup of a page that lists all animals in a species and whether or not they have three photos, a video, and a description. The intent of this report is so that sheleter volunteers can quickly assess what the pet listing needs are.</p>
            </div> <!-- .inside -->
    </div> <!-- .postbox -->

    <div class="postbox" style=" margin:20px 20px 30px 0px">
        <div class="inside"><h3>Having Issues?</h3>
            <p>I'm always happy to help the people who help the animals! If you can't find the information your looking for in the <a href="https://www.airdriemedia.com/petpress">documentation</a>, please reach out.
            <br>Support is handled exlusively through WordPress.org by my one man team - me.</p>
            <p><a href="https://wordpress.org/support/plugin/petpress/" target="_blank" class="button-primary">Get Support</a></p>
        </div> <!-- .inside -->
    </div> <!-- .postbox -->
    If you like <strong>PetPress</strong> please leave a <a href="https://wordpress.org/support/view/plugin-reviews/petpress?filter=5#postform" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating. I mean, a little <a href="https://www.paypal.com/paypalme/airdriemedia">financial support</a> would be nice, but positive reviews are good, too!</p>
    <?php
}

function petpress_register_settings() {
    register_setting( 'petpress_plugin_options', 'petpress_plugin_options', 'petpress_plugin_options_validate' );
    add_settings_section( 'api_settings', 'PetPoint Settings', 'plugin_section_text', 'petpress_plugin' );

    add_settings_field( 'plugin_setting_auth_key', 'PetPoint Auth Key', 'plugin_setting_auth_key', 'petpress_plugin', 'api_settings' );


    //add_settings_field( 'plugin_setting_site_id', 'Site ID', 'plugin_setting_site_id', 'petpress_plugin', 'api_settings' );
   // add_settings_field( 'plugin_setting_start_date', 'Start Date', 'plugin_setting_start_date', 'petpress_plugin', 'api_settings' );


   add_settings_field( 'sizeweight_Checkbox_Element', 'List Options', 'petpress_checkbox_sizeweight_element_callback', 'petpress_plugin', 'api_settings' );


    add_settings_field( 'adoptionpending_Checkbox_Element', '', 'petpress_checkbox_adoptionpending_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'foster_Checkbox_Element', '', 'petpress_checkbox_foster_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'videoicon_Checkbox_Element', '', 'petpress_videoicon_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'hideonhold_Checkbox_Element', '', 'petpress_hideonhold_element_callback', 'petpress_plugin', 'api_settings' );

    add_settings_field('petpointid_Checkbox_Element', 'Show Fields', 'petpress_checkbox_petpointid_element_callback', 'petpress_plugin', 'api_settings');
    add_settings_field( 'housetrained_Checkbox_Element', '', 'petpress_checkbox_housetrained_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field('chipnumber_Checkbox_Element', '', 'petpress_checkbox_chipnumber_element_callback', 'petpress_plugin', 'api_settings');
    add_settings_field( 'daysin_Checkbox_Element', '', 'petpress_checkbox_daysin_element_callback', 'petpress_plugin', 'api_settings' );

    add_settings_field( 'location_Checkbox_Element', '', 'petpress_checkbox_location_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'livedwith_Checkbox_Element', '', 'petpress_checkbox_livedwith_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'nodogcatkid_Checkbox_Element', '', 'petpress_checkbox_nodogcatkid_element_callback', 'petpress_plugin', 'api_settings' );

    add_settings_field( 'behaviorresult_Checkbox_Element', '', 'petpress_checkbox_behaviorresult_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'reasonforsurrender_Checkbox_Element', '', 'petpress_checkbox_reasonforsurrender_element_callback', 'petpress_plugin', 'api_settings' );


    add_settings_field( 'onhold_Checkbox_Element', '', 'petpress_checkbox_onhold_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'price_Checkbox_Element', '', 'petpress_checkbox_price_element_callback', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'sitename_Checkbox_Element', '', 'petpress_checkbox_sitename_element_callback', 'petpress_plugin', 'api_settings' );

    add_settings_field( 'social_Checkbox_Element', 'Social Media', 'petpress_social_element_callback', 'petpress_plugin', 'api_settings' );


    add_settings_field( 'randomphoto_Checkbox_Element', 'Random Photo', 'petpress_checkbox_randomphoto_element_callback', 'petpress_plugin', 'api_settings' );



    //   add_settings_field( 'sn_Checkbox_Element', '', 'petpress_checkbox_sn_element_callback', 'petpress_plugin', 'api_settings' );
    //add_settings_field( 'cat_Checkbox_Element', 'Cats', 'petpress_checkbox_cat_element_callback', 'petpress_plugin', 'api_settings' );
    //add_settings_field( 'otherAnimals_Checkbox_Element', 'Other Animals', 'petpress_checkbox_otheranimals_element_callback', 'petpress_plugin', 'api_settings' );

    //add_settings_field( 'plugin_setting_cache', 'Cache Duration', 'plugin_setting_cache', 'petpress_plugin', 'api_settings' );
    add_settings_field( 'plugin_setting_pagination', 'Pets per page', 'plugin_setting_pagination', 'petpress_plugin', 'api_settings' );

    add_settings_field( 'petpoint_link_Checkbox_Element', 'PetPoint application link', 'petpress_checkbox_petpoint_link_element_callback', 'petpress_plugin', 'api_settings' );

    add_settings_field( 'detail_page_Checkbox_Element', 'Detail pages', 'petpress_checkbox_detail_page_element_callback', 'petpress_plugin', 'api_settings' );


    add_settings_field( 'poweredby_Checkbox_Element', 'Credits', 'petpress_checkbox_poweredby_element_callback', 'petpress_plugin', 'api_settings' );
}

add_action( 'admin_init', 'petpress_register_settings' );

function petpress_checkbox_adoptionpending_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('adoptionpending',$options)){
            $setVal = $options['adoptionpending'];
        }
    }
    echo '<input type="checkbox" id="adoptionpending_checkbox" name="petpress_plugin_options[adoptionpending]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="adoptionpending_checkbox"> "Adoption Pending" post-it notes</label>';
}

function petpress_checkbox_foster_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('foster',$options)){
            $setVal = $options['foster'];
        }
    }
    echo '<input type="checkbox" id="foster_checkbox" name="petpress_plugin_options[foster]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="foster_checkbox"> "In a Foster Home" post-it notes</label>';
}

function petpress_videoicon_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('videoicon',$options)){
            $setVal = $options['videoicon'];
        }
    }
    echo '<input type="checkbox" id="videoicon_checkbox" name="petpress_plugin_options[videoicon]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="videoicon_checkbox"> Video icon (for animals with videos)</label>';
}

function petpress_hideonhold_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('hideonhold',$options)){
            $setVal = $options['hideonhold'];
        }
    }
    echo '<input type="checkbox" id="hideonhold_checkbox" name="petpress_plugin_options[hideonhold]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="hideonhold_checkbox"> Do not show "on hold" animals in list (unchecked means all available animals are listed, even if their "on hold" status is checked in PetPoint)</label>';
}

function petpress_checkbox_sizeweight_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('sizeweight',$options)){
            $setVal = $options['sizeweight'];
        }
    }
    echo '<input type="checkbox" id="sizeweight_checkbox" name="petpress_plugin_options[sizeweight]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="sizeweight_checkbox"> Specific values for Age / Weight (unchecked give general values, such as "adult" and "large")</label>';
}

function petpress_checkbox_petpointid_element_callback()
{
    $options = get_option('petpress_plugin_options');
    $setVal = "0";
    if ($options) {
        if (array_key_exists('petpointid', $options)) {
            $setVal = $options['petpointid'];
        }
    }
    echo '<input type="checkbox" id="petpointid_checkbox" name="petpress_plugin_options[petpointid]" value="1" ';
    echo esc_attr(checked(1, $setVal, false));
    echo '/>';
    echo '<label for="petpointid_checkbox"> PetPoint ID</label>';
}

function petpress_checkbox_chipnumber_element_callback()
{
    $options = get_option('petpress_plugin_options');
    $setVal = "0";
    if ($options) {
        if (array_key_exists('chipnumber', $options)) {
            $setVal = $options['chipnumber'];
        }
    }
    echo '<input type="checkbox" id="chipnumber_checkbox" name="petpress_plugin_options[chipnumber]" value="1" ';
    echo esc_attr(checked(1, $setVal, false));
    echo '/>';
    echo '<label for="chipnumber_checkbox"> Microchip number</label>';
}

function petpress_checkbox_daysin_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('daysin',$options)){
            $setVal = $options['daysin'];
        }
    }
    echo '<input type="checkbox" id="daysin_checkbox" name="petpress_plugin_options[daysin]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="daysin_checkbox"> Number of days in shelter</label>';
}

function petpress_checkbox_housetrained_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('housetrained',$options)){
            $setVal = $options['housetrained'];
        }
    }
    echo '<input type="checkbox" id="housetrained_checkbox" name="petpress_plugin_options[housetrained]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="housetrained_checkbox"> Housetrained</label>';
}

function petpress_checkbox_randomphoto_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('randomphoto',$options)){
            $setVal = $options['randomphoto'];
        }
    }
    echo '<input type="checkbox" id="randomphoto_checkbox" name="petpress_plugin_options[randomphoto]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="randomphoto_checkbox"> Randomize the photos used for each animal on the list page. (If the animal has more than one photo, the first photo, "photo 1", is shown half the time, but the other half of the time one of the other two photos is shown.)</label>';
}


function petpress_checkbox_reasonforsurrender_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('reasonforsurrender',$options)){
            $setVal = $options['reasonforsurrender'];
        }
    }
    echo '<input type="checkbox" id="reasonforsurrender_checkbox" name="petpress_plugin_options[reasonforsurrender]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="reasonforsurrender_checkbox"> Reason for surrender</label>';
}


function petpress_checkbox_behaviorresult_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('behaviorresult',$options)){
            $setVal = $options['behaviorresult'];
        }
    }
    echo '<input type="checkbox" id="behaviorresult_checkbox" name="petpress_plugin_options[behaviorresult]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="behaviorresult_checkbox"> Behavior test result</label>';
}

function petpress_checkbox_livedwith_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('livedwith',$options)){
            $setVal = $options['livedwith'];
        }
    }
    echo '<input type="checkbox" id="livedwith_checkbox" name="petpress_plugin_options[livedwith]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="livedwith_checkbox"> Lived with (animals / children)</label>';
}

function petpress_checkbox_location_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('location',$options)){
            $setVal = $options['location'];
        }
    }
    echo '<input type="checkbox" id="location_checkbox" name="petpress_plugin_options[location]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="location_checkbox"> Location / Sublocation</label>';
}

function petpress_checkbox_nodogcatkid_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('nodogcatkid',$options)){
            $setVal = $options['nodogcatkid'];
        }
    }
    echo '<input type="checkbox" id="nodogcatkid_checkbox" name="petpress_plugin_options[nodogcatkid]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="nodogcatkid_checkbox"> No Dogs / No Cats / No Kids</label>';
}

function petpress_checkbox_onhold_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('onhold',$options)){
            $setVal = $options['onhold'];
        }
    }
    echo '<input type="checkbox" id="onhold_checkbox" name="petpress_plugin_options[onhold]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="onhold_checkbox"> On hold</label>';
}

function petpress_social_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('social',$options)){
            $setVal = $options['social'];
        }
    }
    echo '<input type="checkbox" id="social_checkbox" name="petpress_plugin_options[social]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="social_checkbox"> Show social media links on detail page</label>';
}

function petpress_checkbox_sitename_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('sitename',$options)){
            $setVal = $options['sitename'];
        }
    }
    echo '<input type="checkbox" id="site_checkbox" name="petpress_plugin_options[sitename]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="site_checkbox"> Site name (shows on list page as well as detail page)</label>';
}

function petpress_checkbox_price_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('price',$options)){
            $setVal = $options['price'];
        }
    }
    echo '<input type="checkbox" id="price_checkbox" name="petpress_plugin_options[price]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="price_checkbox"> Price</label>';
}

function petpress_checkbox_petpoint_link_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('petpointlink',$options)){
            $setVal = $options['petpointlink'];
        }
    }
    echo '<input type="checkbox" id="petpointlink_checkbox" name="petpress_plugin_options[petpointlink]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="petpointlink_checkbox"> Create PetPoint application link on detail page. (Check this box if you use PetPoint to process adoption applications)</label>';
}

function petpress_checkbox_detail_page_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('detailpage',$options)){
            $setVal = $options['detailpage'];
        }
    }
    echo '<input type="checkbox" id="detailpage_checkbox" name="petpress_plugin_options[detailpage]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="detailpage_checkbox"> <b>Recommended:</b> Create separate URLs for each animal. This will have benefits for social sharing and analytics gathering. (see <a href="https://www.airdriemedia.com/petpress-version-1-7/">release note</a>)</label>';
}


function petpress_checkbox_poweredby_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";
    if ($options){
        if (array_key_exists('poweredby',$options)){
            $setVal = $options['poweredby'];
        }
    }
    echo '<input type="checkbox" id="poweredby_checkbox" name="petpress_plugin_options[poweredby]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="powered_checkbox"> Allow a small "Powered by PetPress" link to be displayed at the bottom to help other shelters find this plugin. (Not required, but greatly appreciated!)</label>';
}
/*
function petpress_checkbox_sn_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "0";

    if ($options){
        if (array_key_exists('sn',$options)){
            $setVal = $options['sn'];
        }
    }

    echo '<input type="checkbox" id="sn_checkbox" name="petpress_plugin_options[sn]" value="1" ';
    echo esc_attr(checked( 1, $setVal, false ));
    echo '/>';
    echo '<label for="sn_checkbox"> Show spay / neuter status (not yet implemented)</label>';

}

function petpress_checkbox_cat_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    echo '<input type="checkbox" id="cat_checkbox" name="petpress_plugin_options[cats]" value="1" ';
    echo esc_attr(checked( 1, $options['cats'], false ));
    echo '/>';
    echo '<label for="cat_checkbox"> Allow user to switch to list of cats</label>';
}

function petpress_checkbox_otheranimals_element_callback() {
    $options = get_option( 'petpress_plugin_options' );
    echo '<input type="checkbox" id="otheranimals_checkbox" name="petpress_plugin_options[otheranimals]" value="1" ';
    echo esc_attr(checked( 1, $options['otheranimals'], false ));
    echo '/>';
    echo '<label for="otheranimals_checkbox"> Allow user to switch to list of other animals</label>';
}
*/


function plugin_section_text() {
    echo '<p>Here you can set all the options for using the petpress plugin. For a more thorough explanation of the options and how they are used, check the <a href="https://www.airdriemedia.com/petpress">documentation</a>.';
}

function plugin_setting_auth_key() {
    $options = get_option( 'petpress_plugin_options' );
    $setVal = "";

    if ($options){
        $setVal = esc_attr( $options['auth_key'] );
        if  (is_null($setVal) || $setVal == "") {
            $setVal = ""; // default value for cache is 1440 min - one day
        } 
    }
    echo "<input style='width:60ch' id='plugin_setting_auth_key' name='petpress_plugin_options[auth_key]' type='text' value='";
    echo esc_attr($setVal);
    echo "' />";
    echo "<br>This is your <a href='https://www.24petcare.com'>PetPoint</a> Authorization Key, Found in PetPoint at <i>\"Admin > Admin Options > Setup > Online Animal Listing Options\"</i>. <span style=\"color:darkred; font-weight:bold\">Note for first-time users:</span> Please be aware that the web services of PetPoint are not enabled by default. You must contact PetPoint support and request that web services be enabled for your organization. If you attept to use PetPress without web services enabled, you will see \"Access Denied\" errors.\n";
}

function plugin_setting_pagination() {
    $options = get_option( 'petpress_plugin_options' );

    $setVal = ""; // 
    if ($options){
        $setVal = $options['pagination'];
        if  (is_null($setVal) || $setVal == "") {
            $setVal = "40"; // default value for cache is 1440 min - one day
        } 
    }
    else{
        $setVal = "40";
    }

    echo "<select name='petpress_plugin_options[pagination]' id='plugin_setting_pagination'>";
    echo "<option value='20' ";
    if (esc_attr( $setVal ) == 20){
        echo "SELECTED";
    }
    echo ">20</option>";
    echo "<option value='40' ";
    if (esc_attr( $setVal ) == 40){
        echo "SELECTED";
    }
    echo ">40</option>";
    echo "<option value='60' ";
    if (esc_attr( $setVal ) == 60){
        echo "SELECTED";
    }
    echo ">60</option>";
    echo "<option value='80' ";
    if (esc_attr( $setVal ) == 80){
        echo "SELECTED";
    }
    echo ">80</option>";
    echo "<option value='100' ";
    if (esc_attr( $setVal ) == 100){
        echo "SELECTED";
    }
    echo ">100</option>";
    echo "<option value='200' ";
    if (esc_attr( $setVal ) == 200){
        echo "SELECTED";
    }
    echo ">200</option>";
    

    echo "</select>";

    //echo "<input id='plugin_setting_pagination' name='petpress_plugin_options[pagination]' type='text' size='3' maxlength='3' value='";
    //echo esc_attr( $setVal );
    //echo "' />";
    //echo "<label for='plugin_setting_cache'> Length of cache, in minutes</label>";
    echo " Number of pets to show on each page of a list.";
}

class petpress_ShowStopper extends Exception {};

register_activation_hook(__FILE__, 'PetPress_activate');
register_deactivation_hook(__FILE__, 'PetPress_deactivate');
register_uninstall_hook(__FILE__, 'PetPress_uninstall');

add_action( 'wp_enqueue_scripts', 'petpress_load_dashicons_front_end' );

function petpress_load_dashicons_front_end() {
    wp_enqueue_style ( 'dashicons' );
}

add_shortcode('PETPRESS','petpress_main');
add_shortcode('petpress','petpress_main');
add_shortcode('PetPress','petpress_main');


function petpress_main($atts=[], $content = null)
{   
    $utils = new petpress_utilities_class();
    //if ( is_admin() || is_user_logged_in() ) { return "(So as to not interfere with page editors, there is no PetPress output when in Wordpress admin mode. To see PetPress output, log out of Wordpress or open this page in another browser.)"; } // do not process shortcode in admin

    //    if ( isset($_GET['et_pb_preview']) && true == $_GET['et_pb_preview'] ) { return ""; } // do not process shortcode in divi editor
    //    if ( isset($_GET['et_fb']) && true == $_GET['et_fb'] ) { return ""; } // do not process shortcode in divi editor

    if($utils->getAuthKey($authKey) == false)
    {
        // Some error happened, output the error and be done.
        return ($authKey);
    }
    // If an animal was passed in QueryString, show it.

    if (array_key_exists("site",$atts)){
        $site = $atts['site'];
    }
    else{
        $site = "0";
    }


    //if (isset($_GET['vols']) && (htmlspecialchars($_GET['vols']))){
    //    return petpress_showVolInfo($atts, $site, $authKey);
    //}

    if (isset($_GET['id']) && (htmlspecialchars($_GET['id']))){
        try {
          //  throw new petpress_ShowStopper("Query String ID temporatily disabled (LN#" . __LINE__ . ")");
            return petpress_showOneAnimal(htmlspecialchars($_GET['id']),$atts,$authKey);
        }
        catch (petpress_ShowStopper $e) {
            echo esc_html($e->getMessage());
            return;
        }
    }
    
    if (isset($_GET['species']) && (htmlspecialchars($_GET['species']))){
        try {

         //   return petpress_showList(htmlspecialchars($_GET['species']),$atts, $authKey);
         $theRoster = petpress_getRoster($_GET['species'], $site, $authKey, false);
         if (is_array($theRoster)) {
             return petpress_showRoster($_GET['species'],$atts,$theRoster);
         }
         else
         {
             return $theRoster;
         }
        }
        catch (petpress_ShowStopper $e) {
            echo esc_html($e->getMessage());
            return;
        }
    }

    // If an animal was passed in shortcode, show it.
    if (array_key_exists("id",$atts)){
        try {
            return petpress_showOneAnimal(htmlspecialchars($atts['id']), $atts, $authKey);
        }
        catch (petpress_ShowStopper $e) {
            echo esc_html( $e->getMessage());
            return;
        }
    }


    if (array_key_exists("report",$atts)){
        try {
            if (strtolower($atts["report"]) == "found"){
                return petpress_getFoundRoster($atts['species'], $authKey, "false");    
            }
            if (strtolower($atts["report"]) == "vols"){
                return petpress_showVolInfo($atts, $site, $authKey);    
            }
            if (strtolower($atts["report"]) == "purge"){
                return petpress_purgeCacheData($atts, $site, $authKey);    
            }

            if (strtolower($atts["report"]) == "daysin"){
                return petpress_daysInReport($atts, $site, $authKey);    
            }
        }
        catch (petpress_ShowStopper $e) {
            echo esc_html( $e->getMessage());
            return;
        }
    }

    // If a species was in the shortcode, show it.
    if (array_key_exists("species",$atts)){
        try {
             //   return petpress_showList(htmlspecialchars($atts['species']),$atts, $authKey);
             $theRoster = petpress_getRoster($atts['species'], $site, $authKey, false);
             if (is_array($theRoster)) {
                return petpress_showRoster($atts['species'],$atts,$theRoster);
             }
             else
             {
                 return $theRoster;
             }
        }
        catch (petpress_ShowStopper $e) {
            echo esc_html( $e->getMessage());
            return;
        }
    }


    return "PetPress configuration error - need species ID or individual animal ID. Please check the documentation.";
}


function petpress_daysInReport($attsIN, $siteIN, $authKeyIN){
    
    try {

        $theRoster = petpress_getRoster($attsIN["species"], $siteIN, $authKeyIN, false);

        $html = "";    
        if (is_array($theRoster)) {

            $sortfunc = "petpress_comparedaysin";
            if (function_exists($sortfunc)){
                try {
                    usort($theRoster, $sortfunc);

                    foreach ($theRoster as $critter)
                    {
                        if (true) {
                            $html .= $critter->get_name() . ": " . $critter->get_daysin() ." days<br>\n";
                        }
                    }
                    return $html;
                }
                catch (Exception $e) {
                    throw new petpress_ShowStopper("Unknown or unspported sort criteria (LN#" . __LINE__ . ": " . $e->getMessage() .")");
                }
            }
            else
            {
               // $content .= "<!-- petpress non-fatal error: tried to sort but couldn't. Check for an invalid 'sort' parameter in the shortcode or query string. -->";
            }

     }
    
    }
    catch (petpress_ShowStopper $e) {
            echo esc_html( $e->getMessage());
            return;
    }  
}

function petpress_showVolInfo($attsIN, $site, $authKey){
    
    try {
        $utils = new petpress_utilities_class();
        $theRoster = petpress_getRoster($attsIN["species"], $site, $authKey, false);
        if (is_array($theRoster)) {


        // create proofsheet

        $count = 0;

        $speciesName = petpress_getSpeciesfromSpeciesID($attsIN["species"]);
   
        $content = "\n<!-- Proof Sheet by PetPress - www.AirdrieMedia.com/petpress  [v" . petpress_kVersion . "] -->\n";
        $content .= "<div id='pp_wrapper'>\n";
    
     
        if (empty($theRoster)) {
            return "There do not seem to be any animals of the selected species at this site. \n";
        }
        
        $content .= "\n<div id='pp_list'>\n";
    
        $options = get_option( 'petpress_plugin_options' );
     
        $details = "";
        $noPhotoList = "";
        $noWriteUpList = "";
        $noVideoList = "";
    
        foreach ($theRoster as $critter)
        {
            $count++;
            if (true) {
                $details .= "\n<div id ='pp_id" . $critter->get_id() . "' xpp_" . $critter->get_sex() ."'>\n";
    
                $details .= "\n<h3 class='pp_name' style='padding-top:0px;padding-bottom:0;margin:0'>";
                $details .= "<a class='xpp_" . $critter->get_sex() . "' href='?id=" . $critter->get_id() . "'>";
                
                //$content .= "testingtest ";
                

                    $details .=  $critter->get_name() . "</a>";

                    if ($critter->adoptionPending())
                    {
                        $details .= " <span class='dashicons dashicons-heart'></span> ";
                    } 
                    if ($critter->isInFoster())
                    {
                        $details .= " <span class='dashicons dashicons-admin-home'></span> ";
                    }
    
                //if (strlen($critter->videoid)>5){
                //    $content .= '<span class="dashicons dashicons-format-video pp_video_icon"></span>';
                //}
                $details .= "</h3>\n";
                $details .= "<!--datasource:" . $critter->get_datasource() . " (" . $critter->get_time() . ")-->\n";
                if (strlen($critter->get_breed()) > 32) { 
                    $details .=  substr($critter->get_breed(),0,30) ."... &middot; \n";
                }
                else {
                $details .=  $critter->get_breed() ." &middot; \n";
                }
                $details .= $critter->approximateAge() . " | " . strtolower($critter->get_sex()) . " | " . $critter->get_shortWeight() . " | " . $critter->get_daysin() . " days in shelter | ";
            if ($utils->optionChecked("sitename") || (array_key_exists("showsite",$attsIN))) {
                if (strlen($critter->get_sitename()) > 25) { 
                    $details .=  " &middot; " . substr($critter->get_sitename(),0,23) ."...\n";
                }
                else {
                    $details .=  " &middot; " . $critter->get_sitename() ."\n";
                }
                }
                if (strlen($critter->get_location()) > 1) { 
                    $details .= $critter->get_location() ." ";    
                    if (strlen($critter->get_sublocation()) > 1) { 
                        $details .= "/ " . $critter->get_sublocation() ;
                    }
                    $details .= "\n";
                }
                $details .= "<br>\n";

                $photocount = 0;

                //$pos = strpos($critter->get_photo1(), 'Not-Available');
                $pos = strpos($critter->get_photo1(), 'airdriemedia');
                if ($pos === false) { $photocount++; }
                if (strlen($critter->get_photo2()) > 5) { $photocount++; }
                if (strlen($critter->get_photo3()) > 5) { $photocount++; }

                if ($photocount < 3) {
                    $details .= "<b style='color:#F00'>NEEDS A PHOTO<br></b>\n";
                    $noPhotoList .= "<a href='?id=" . $critter->get_id() . "'";
                    if ($photocount < 1) {$noPhotoList .= " style='color:#c00; font-weight:bold' ";}
                    
                    $noPhotoList .= ">" . $critter->get_name() . "</a>";
                    if ($photocount < 1) {$noPhotoList .= "</span>";}
                    $noPhotoList .= " (" . $critter->get_breed() . ") ";

                    if ($critter->adoptionPending())
                    {
                        $noPhotoList .= " <span class='dashicons dashicons-heart'></span> ";
                    } 
                    if ($critter->isInFoster())
                    {
                        $noPhotoList .= " <span class='dashicons dashicons-admin-home'></span> ";
                    }
                    $noPhotoList .= "<br> ";
                }
                if (strlen($critter->get_memo()) < 5) {
                    $details .= "<b style='color:#C00'>NEEDS WRITE-UP<br></b>\n";
                    $noWriteUpList .= "<a href='?id=" . $critter->get_id() . "'>" . $critter->get_name() . "</a> (" . $critter->get_breed() . ") ";
                    
                    if ($critter->adoptionPending())
                    {
                        $noWriteUpList .= " <span class='dashicons dashicons-heart'></span> ";
                    } 
                    if ($critter->isInFoster())
                    {
                        $noWriteUpList .= " <span class='dashicons dashicons-admin-home'></span> ";
                    }
                    
                    $noWriteUpList .= "<br>";
                }
                if (strlen($critter->get_videoid())<5){
                    $details .= "<b style='color:#a00'>NEEDS VIDEO</b><br>\n";
                    $noVideoList .= "<a href='?id=" . $critter->get_id() . "'>" . $critter->get_name() . "</a> (" . $critter->get_breed() . ") ";
                    
                    if ($critter->adoptionPending())
                    {
                        $noVideoList .= " <span class='dashicons dashicons-heart'></span> ";
                    } 
                    if ($critter->isInFoster())
                    {
                        $noVideoList .= " <span class='dashicons dashicons-admin-home'></span> ";
                    }
                    
                    
                    $noVideoList .= "<br>";
                }
                $details .= "<a href='?id=" . $critter->get_id() ."'>";
    
                $details .= "<img style='height:100px' src='" . $critter->get_photo1() . "'>";
                $details .= "<img style='height:100px' src='" . $critter->get_photo2() . "'>";
                $details .= "<img style='height:100px' src='" . $critter->get_photo3() . "'>";

                $details .= "</a>\n";
                $details .= "</div></a><hr><div style='clear:all'>\n"; // end of one pet
        
            $details .= "</div>\n";
        }
        }
        $details .= "</div>\n";
        $content .= "<div id='pp_iconkey'>";
        $content .= " <span class='dashicons dashicons-heart'></span> = Adoption Pending<br>";
        $content .= " <span class='dashicons dashicons-admin-home'></span> = In a Foster Home<br>";
        $content .= " </div>";
        if ($noPhotoList != "") {
            $content .= "<h3>Photos Needed</h3><p>Red indicates that there are no photos yet.</p><ul>" . $noPhotoList . "</ul>";
        }
        if ($noWriteUpList != "") {
            $content .= "<h3>Write-ups Needed</h3><ul>" . $noWriteUpList . "</ul>";
        }
        if ($noVideoList != "") {
            $content .= "<h3>Videos Needed</h3><ul>" . $noVideoList . "</ul>";
        }

    $content .= "<hr><h3>Details</h3>";

        $content .= $details;

        $content .= "<div style='clear:both' id='pp_animalcount'>" .  $count . " " . $speciesName . "s shown";
    
        //$options = get_option( 'petpress_plugin_options' );
    
        $content .= "<span";
    
        if ($options){
            if (array_key_exists("poweredby",$options) && ($options['poweredby'] == 1) ){
                $content .= " class='pp_poweredby'";
            }
            else {
                $content .= " class='pp_poweredby_disabled'";
            }
        }
        $content .= "> | Powered by <a href='https://www.airdriemedia.com/petpress'>PetPress</a></span>";
    
        $content .= "</div>\n";
        $content .= "</div>\n\n"; // end wrapper



         return $content;
     }
     else
     {
         return $theRoster;
     }
    }
    catch (petpress_ShowStopper $e) {
        echo esc_html($e->getMessage());
        return;
    }
    
}

function petpress_purgeCacheData($attsIN, $site, $authKey){
    $html = "<h3>Request to purge cache data</h3>";

    $html .= <<<HTML
    <p>This page has been create to allow you to purge the animal data from the local database.
        With the animal data deleted, PetPress will have to retrieve data from PetPoint the next time
        it is requested. The original data in PetPoint is not affected.</p>
        <p>Under normal circumstances, you will have the best user experience <i>not</i> using this purge function and letting PetPress
        manage the data updates automatically. However, this feature is offered for the rare cases in which updates must appear on the site without any delay.</p>
        <ul><li><b>PROS: </b>You do not have to wait for the normal cache refresh timeout. This may be important if you accidentally
        made changes to PetPoint that you do not want to be on the site, or if you have an urgent change that cannot wait.</li>
        <li><b>CONS: </b>Retrieving data from PetPoint is slow, which is why PetPess doesn't do it every time a page is requested. The next
        visitor to a pet listing page will have to wait several additional seconds (the duration is dependent on the number animals) to see the pet listings.
        Once this is done, the retrieved data will be in cache and the plugin will operate normally again.</li></p>
        <p>Under normal circumstances, you will have the best user experience <i>not</i> using this purge function and letting PetPress
        manage the data updates automatically. However, if you want to force the plugin to retrieve the most recent data, using this
        function does not pose a threat to your website or PetPoint data.</p>
        <p>To continue, enter your six-character authorization code in the box and click "Purge Cache Data"</p>
    HTML;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ac = $_POST['ac'];
    if (empty($ac)) {
      $html .= "ac is empty";
    }
    else
    {

        $html .= "<!-- AuthKey is [" . $authKey . "] -->";
        // The authorization code is the last six characters of the AuthKey
        if (substr($authKey,-6) == $ac)
        {
            $html .= "<br>VALID KEY<br>";
            // delete rosters
            global $wpdb;

            $table_name = $wpdb->prefix . "petpress_sites"; 
            //$query = "delete from " . $table_name . " where site='" . $site ."'";
            $query = "delete from " . $table_name ;
            $result = $wpdb->query($query);

            $table_name = $wpdb->prefix . "petpress_animals"; 
            $query = "delete from " . $table_name ;
            $result = $wpdb->query($query);

            $html .= "<p style='color:#0c0'>The cache has been cleared";

        }
        else 
        {
            $html .= "<p style='color:#c00'>The code you entered (" . $ac . ") is not correct. Please check with your website administrator for the correct code.";
        }
    }
}

$html .= <<<HTML
    <form method=post  onsubmit="return(checkFieldLength(document.getElementById('ac').value))">
    <input type='text' name='ac' id='ac'>
    <input type='submit' value='Purge Cache Data'>

    </form>

    <script language="javascript">

    function checkFieldLength(acIN)
    {
        if (acIN.length != 6)
        {
        	alert("That code is not valid, please check the authorization code and try again");
            return false;
        }
        return true;
    }
    </script>
HTML;

    return $html;
}




function petpress_showOneAnimal($idIN, $attsIN, $authKeyIN){
    $utils = new petpress_utilities_class();
    if (strlen($idIN) < 3){
        throw new petpress_ShowStopper("No ID passed to function to show animal (LN#" . __LINE__ . ")");
    }
    else
    {
        $critter = petpress_getOneAnimal($idIN, $authKeyIN, false);

        $cName = $critter->get_name();
        $cSpecies = $critter->get_species();
        $cPhoto1 = $critter->get_photo1();
        $cPhoto2 = $critter->get_photo2();
        $cPhoto3 = $critter->get_photo3();

        //$html = petpress_getStyleBlock();
        $html = "<script>document.title = '" . $cName ." the " . $cSpecies . " (" . $critter->get_breed() .")';</script>\n";
        $html .= "\n<!-- Listings by PetPress - www.AirdrieMedia.com/petpress [v" . petpress_kVersion . "] -->";
        $html .= "\n<div id='pp_wrapper'>\n";
        $html .= "<h2 id='pp_headline'>Meet " . $cName . "</h2>\n";
        $html .= "<!--datasource:" . $critter->get_datasource() . " (" . $critter->get_time() . ")-->\n";
        if (strlen($critter->get_videoid()) > 5){
            $html .=  "<fieldset  class='pp_fieldset' style=\"margin-bottom:30px\">";
            $html .=  "<legend>Video Introduction</legend>";
            $html .=  "<div class=\"pp_ytvideo\">";
            $html .= "<iframe src=\"https://www.youtube.com/embed/" . $critter->get_videoid() . "?rel=0\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>\n";
            $html .=  "</div></fieldset>";
        }
        $html .= "<div id='pp_intro'>\n<fieldset class='pp_fieldset'>\n<legend>All about " .$cName . "</legend>\n";
        $html .= "<p id='pp_introbasicstats'>" . $cName . " is a " ;
        if ($critter->get_age() >0){
            $html .= $critter->formatAge() . " old " ;
        }
        $html .= strtolower($critter->get_sex()) ;
        $html .= " " . $critter->get_breed();
        
        if (($critter->get_secondarybreed() != "") && ($critter->get_secondarybreed() != "\n  ")){
            
            if (strpos(strtolower($critter->get_breed()), "mix") === false ) { // add secondary breed unless primary is a mix
                if (strcmp($critter->get_breed(), $critter->get_secondarybreed()) !== 0){
                    $html .= "/" . $critter->get_secondarybreed();
                }
            } 
            
        }
        
        if ($cSpecies != "Dog") $html .= " " . strtolower($cSpecies);
        if ($critter->get_weight() >0) {
            $html .= " who weighs " . $critter->get_weight() . " pounds";
        }
        $options = get_option( 'petpress_plugin_options' );
        if ($options){
            if (array_key_exists("daysin",$options)){
                if ($options['daysin'] == 1) { // checked
                    $html .= " and has been with us for " . $critter->get_daysin() . " days";
                }
            }
        }
        $html .= ".</p>\n";

        if (($utils->optionChecked("foster")) && ($utils->optionChecked("adoptionpending")) && $critter->adoptionPending() && $critter->isInFoster())
        {
            $html .= "<p class='pp_adoption_pending_note'>" . $cName . " is currently in a foster home and has an adoption pending.</p>";
        }
        else {
        if ($utils->optionChecked("foster")){
            if ($critter->isInFoster()){
                $html .= "<p class='pp_foster_note'>" . $cName . " is currently in a foster home.</p>";
            }
        }

        if ($utils->optionChecked("adoptionpending")){
            if ($critter->adoptionPending()){
                $html .= "<p class='pp_adoption_pending_note'>" . $cName . " has an adoption pending.</p>";
            }
        }
    }


        $html .= "<div id='pp_memo'>" . $critter->get_memo() . "</div>\n";

        if (($critter->get_age() < 18) && ($critter->get_age() > 0) && (($cSpecies == "Dog") || ($cSpecies == "Cat")) ){
            $html .= "<p id='pp_sizenote'><i>Note that many " . strtolower($cSpecies) . "s do not reach full size until they are about a year and a half old. The weight listed here is the " . strtolower($cSpecies) . "'s current weight.</i></p>";
        }

        if ($utils->optionChecked("social")){
            $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
            //$fblink = "<p id='pp_sociallinks'><a href='https://www.facebook.com/sharer/sharer.php?u=" . $url . "'>Post about " . $critter->name . " on your Facebook page.</a></p>";
            if (isset($_POST['socialurl'])){
            $fblink = "<p id='pp_sociallinks'><a href='https://www.facebook.com/sharer/sharer.php?u=" . $_POST['socialurl'] . "'>Post about " . $cName . " on your Facebook page.</a></p>";
            $html .= $fblink;    
            }
            
        }
        $html .= "</fieldset>\n</div>\n";
        $html .= "<div id='pp_photosection'>\n<fieldset class='pp_fieldset'><legend>Photos (click for full-size)</legend>\n";

        $html .= '<div class="pp_photodiv"><img class="pp_lightbox-trigger" data-index="0" src="' . $cPhoto1 .'" alt="' . $cName .' 1"></div>';
        if ($cPhoto2 != ""){
        $html .= '<div class="pp_photodiv"><img class="pp_lightbox-trigger" data-index="1" src="' . $cPhoto2 .'" alt="' . $cName .' 2"></div>';
        }
        if ($cPhoto3 != ""){
        $html .= '<div class="pp_photodiv"><img class="pp_lightbox-trigger" data-index="2" src="' . $cPhoto3 .'" alt="' . $cName .' 3"></div>';
        }
        $html .= "</fieldset>\n";

        if ($utils->optionChecked("housetrained") 
            || $utils->optionChecked("onhold")
            || $utils->optionChecked("price") 
            || $utils->optionChecked("sitename")
            || $utils->optionChecked("behaviorresult")   
            || $utils->optionChecked("livedwith") 
            || $utils->optionChecked("location") 
            || $utils->optionChecked("nodogcatkid")
            || $utils->optionChecked("chipnumber")
            || $utils->optionChecked("petpointid") 
            || $utils->optionChecked("reasonforsurrender") 
            || array_key_exists("showsite",$attsIN)) {

            $html .= "<div id='pp_moreinfo'>\n<fieldset class='pp_fieldset'><legend>Additional Information</legend>\n";

            if ($utils->optionChecked("petpointid")) {
                if (strlen($critter->get_id()) > 1) {
                    $html .= "<p>ID number: " . $critter->get_id() . "</p>\n";
                }
            }

            if ($utils->optionChecked("housetrained")) {
            if (strlen($critter->get_housetrained()) > 1) { 
                    $html .= "<p>Housetrained: ";
                    if ($critter->get_housetrained() == "Unknown"){
                        $html .= "Unknown or unspecified";
                    }
                    else{
                        $html .= $critter->get_housetrained();
                    }
                    $html .= "</p>\n";
                }
            }
            
            if ($utils->optionChecked("livedwith")) {
                if (strlen($critter->get_livedwithanimals()) > 1) { 
                    $html .= "<p>Has lived with animals: " . $critter->get_livedwithanimals() ."</p>\n";
                }
                if (strlen($critter->get_livedwithanimaltypes()) > 1) { 
                    $html .= "<p>Types of animals lived with: " . $critter->get_livedwithanimaltypes() ."</p>\n";
                }
                if (strlen($critter->get_livedwithchildren()) > 1) { 
                    $html .= "<p>Has lived with children: " . $critter->get_livedwithchildren() ."</p>\n";
                }
            }            
            if ($utils->optionChecked("nodogcatkid")) {
                if (strlen($critter->get_nodogs()) > 1) { 
                    $html .= "<p>No Dogs: " . $critter->get_nodogs() ."</p>\n";
                }
                if (strlen($critter->get_nocats()) > 1) { 
                    $html .= "<p>No Cats: " . $critter->get_nocats() ."</p>\n";
                }
                if (strlen($critter->get_nokids()) > 1) { 
                    $html .= "<p>No Kids: " . $critter->get_nokids() ."</p>\n";
                }
            }

            
            if ($utils->optionChecked("reasonforsurrender")) {
                if (strlen($critter->get_reasonforsurrender()) > 1) { 
                    $html .= "<p>Reason for surrender: " . $critter->get_reasonforsurrender() ."</p>\n";
                }
            }

            if ($utils->optionChecked("behaviorresult")) {
                if (strlen($critter->get_behaviorresult()) > 1) { 
                    $html .= "<p>Behavior test result: " . $critter->get_behaviorresult() ."</p>\n";
                }
            }

            if ($utils->optionChecked("price")) {
                if (strlen($critter->get_price()) > 1) { 
                    $html .= "<p>Price: $" . $critter->get_price() ."</p>\n";
                }
            }

            if ($utils->optionChecked("sitename") || (array_key_exists("showsite",$attsIN))) {
                if (strlen($critter->get_sitename()) > 1) { 
                    $html .= "<p>Site: " . $critter->get_sitename() ."</p>\n";
                }
            }

            if ($utils->optionChecked("location")) {
                if (strlen($critter->get_location()) > 1) { 
                    $html .= "<p>Location: " . $critter->get_location() ." ";    
                    if (strlen($critter->get_sublocation()) > 1) { 
                        $html .= "/ " . $critter->get_sublocation() ;
                    }
                    $html .= "</p>\n";
                }
            }
                   

            if ($utils->optionChecked("onhold")) {
                if (strlen($critter->get_onhold()) > 1) { 
                    $html .= "<p>On Hold: " . $critter->get_onhold() ."</p>\n";
                }
            }


            if ($utils->optionChecked("chipnumber")) {
                if (strlen($critter->get_chipnumber()) > 1) {
                    $html .= "<p>Microchip number: " . $critter->get_chipnumber() . "</p>\n";
                }
            }

            $html .= "</fieldset></div>\n";
        }

        // Added for FB BEGIN
        $uri = $_SERVER['REQUEST_URI'];
        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $pageURL = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        


        if ($utils->optionChecked("petpointlink")){
            if ($critter->get_adoptionapplicationurl() != null)
            {
                //$name = $critter->get_name();
                $adoptionapplicationurl = $critter->get_adoptionapplicationurl();
                $html .= "<div id='pp_petpointlink_div'><a id='pp_petpointlink' href='$adoptionapplicationurl' target='_blank'> Interested in $cName? Apply to adopt!</a></div>";
            }
        }

        // PetPoint link END


$lightboxscript = <<< lightboxcode
<div id="pp_lightbox">
    <span id="pp_lightboxCloseBtn">&times;</span>
    <span id="pp_lightboxPrevBtn">&lt;</span>
    <img id="pp_lightboxImg" src="" alt="Lightbox Image">
    <span id="pp_lightboxNextBtn">&gt;</span>
</div>
<script>
var cPhoto1 = "$cPhoto1";
var cPhoto2 = "$cPhoto2";
var cPhoto3 = "$cPhoto3";
</script>
lightboxcode;

        $html .= $lightboxscript;

        return $html;
    }
}

function petpress_makeLinkURL($parmIN, $parmValIN){
    $qs = "";
    $connector = "?";
    foreach ($_GET as $key => $value){
        if ($key != $parmIN){
            $qs .= $connector . $key . "=" . $value;
            $connector = "&";
        }
       
    }
    $qs .= $connector . $parmIN . "=" . $parmValIN;
    return $qs; 
}

function petpress_makePaginationLinks($petsperpageIN, $numpagesIN, $pagenumIN) {
    $content = "";
    if ($numpagesIN > 1){
    $content .= "<div class='pp_paginationlinkset'>";
    if ($numpagesIN > 1){
        if($pagenumIN == 1){
            $content .= " << &middot; ";
        }
        else{
            $content .= "<a href='". petpress_makeLinkURL("pg" , $pagenumIN-1) . "'> << </a> &middot; ";
        }
    }


    for ($x=1; $x<=$numpagesIN; $x++)
    {
        if ($x != $pagenumIN)
        {
        $content .= "<a href='". petpress_makeLinkURL("pg" , $x) . "'>". $x . "</a> &middot; ";
        }
        else{
            $content .=  "<span class='curpage'>" . $x . "</span> &middot; ";
        }
    }

    if ($numpagesIN > 1){
        if($pagenumIN == $numpagesIN){
            $content .= " >> ";
        }
        else{
            $content .= "<a href='". petpress_makeLinkURL("pg" , $pagenumIN+1) . "'> >> </a>";
        }
    }
    $content .= "</div>"; // pp_paginationlinkset
    }
    return $content;
}

function petpress_showRoster($speciesIN,$attsIN,$rosterIN) {
    $utils = new petpress_utilities_class();
    $count = 0;
    $options = get_option( 'petpress_plugin_options' );
    $accentclass="";
    if ($utils->optionChecked("coloraccents")) {
        $accentclass="";
    }
    else {
        $accentclass=" noaccentcolor ";
    }

    $speciesName = petpress_getSpeciesfromSpeciesID($speciesIN);
    /*
    if     ($speciesIN == "1") { $speciesName = "Dog";  }
    elseif ($speciesIN == "2") { $speciesName = "Cat"; }
    elseif ($speciesIN == "3") { $speciesName = "Rabbit"; }
    elseif ($speciesIN == "4") { $speciesName = "Horse"; }
    else                       { $speciesName = "Other Animal"; }
    */
    //$content = petpress_getStyleBlock();
    $content = "\n<!-- Listings by PetPress - www.AirdrieMedia.com/petpress [v" . petpress_kVersion . "] -->\n";
    $content .= "<div id='pp_wrapper'>\n";

    $content .= "<h2 id='pp_headline'>";
    if (array_key_exists("heading",$attsIN)) {
        $content .= htmlspecialchars($attsIN['heading']);
    }
    else{
        $content .= "Adoptable " . $speciesName . "s" ;
    }
    $content .= "</h2>\n";

    // START quickref links

    if (count($rosterIN) > 8){

    $content .= "<div id='pp_jumpto'><select onchange=(document.location=this.options[this.selectedIndex].value)>";

    $content .= "<option value=''>-- Find by name --</option>";
    foreach ($rosterIN as $critter){
    //    $content .= "<option value='". $critter->get_id() . "'>";
    $content .= "<option value='". $utils->detailPageURL($critter->get_id(), $critter->get_name()) . "'>";
        $content .= $critter->get_name() . " - " ;
        
        if (strlen($critter->get_breed()) > 22) { 
            $content .=  substr($critter->get_breed(),0,20) ."...";
        }
        else {
            $content .=  $critter->get_breed() ;
        }
        
        $content .= "</option>";
    }
    $content .= "/<select></div>";
    }
    // END quickref links

    $sortmessage = "sorted by name";
    if (isset($_GET['sort']) && (htmlspecialchars($_GET['sort']))){
        if (htmlspecialchars($_GET['sort']) == "age"){
            $sortmessage = "sorted by oldest to youngest";
        }
        if (htmlspecialchars($_GET['sort']) == "weight"){
            $sortmessage = "sorted by smallest to largest";
        }
    }

    $content .= "<div id=pp_sortmessage>" . $sortmessage . "</div>";

    $content .= "<div id='pp_sortlink'><a href='" . petpress_makeLinkURL("sort","name") ."'>by Name</a> | <a href='" . petpress_makeLinkURL("sort","age") ."'>by Age</a> | <a href='" . petpress_makeLinkURL("sort","weight") ."'>by Size</a></a></div>\n\n";
    //$roster = petpress_getListOfAnimals($speciesIN, $attsIN, $authKeyIN);

    if (empty($rosterIN)) {
        return "There do not seem to be any animals of the selected species at this site. \n";
    }

    if (isset($_GET['sort']) && (htmlspecialchars($_GET['sort'])))
    {
        $sortfunc = "petpress_compare" . htmlspecialchars($_GET['sort']);
    }
    elseif (array_key_exists("sort",$attsIN)) {
        $sortfunc = "petpress_compare" . htmlspecialchars($attsIN['sort']);
    }

    if (isset($sortfunc))
    {
        if (function_exists($sortfunc)){
            try {
                usort($rosterIN, $sortfunc);
            }
            catch (Exception $e) {
                throw new petpress_ShowStopper("Unknown or unspported sort criteria (LN#" . __LINE__ . ": " . $e->getMessage() .")");
            }
        }
        else
        {
            $content .= "<!-- petpress non-fatal error: tried to sort but couldn't. Check for an invalid 'sort' parameter in the shortcode or query string. -->";
        }
    }
    $content .= "\n<div id='pp_list'>\n";

    
    $randomizephotos = false;
    $separateDetailPages = false;
    if ($options){
        if (array_key_exists("randomphoto",$options) && ($options['randomphoto'] == 1) ){
            $randomizephotos = true;
        }

        if (array_key_exists("detailpage",$options) && ($options['detailpage'] == 1) ){
            $separateDetailPages = true;
        }
        
        
        $petsperpage = 100; // most pets allowed
        if (array_key_exists("pagination",$options) ){
            $petsperpage = $options['pagination'];
        }
        $numpages = ceil(count($rosterIN) / $petsperpage);
        
        if ( isset($_GET['pg']) && is_numeric($_GET['pg'] )) {
            $pagenum = $_GET['pg'];
        }
        else {
            $pagenum = 1;
        } 
        $startpet = ($pagenum-1) * $petsperpage + 1;
        $endpet = $startpet + $petsperpage;
        $content .= petpress_makePaginationLinks($petsperpage, $numpages, $pagenum);
    }
    
    foreach ($rosterIN as $critter)
    {
        $cBreed = $critter->get_breed();
        $cWeight = $critter->get_weight();
        $cAgegroup = $critter->get_agegroup();

        $count++;
        if (($count >= $startpet) && ($count < $endpet)) {
            $content .= "\n<div id ='pp_id" . $critter->get_id() . "' class='pp_listitem pp_" . $critter->get_sex() ."'>\n";

            //$content .= "\n<h3 class='pp_name' >";
           
            $videoicon = "";
            if (array_key_exists("videoicon",$options) && ($options['videoicon'] == 1) ){
                if (strlen($critter->get_videoid())>5){
                    $videoicon .= '<span class="dashicons dashicons-format-video pp_video_icon"></span>';
                }
            }
        
            $content .= "<!--datasource:" . $critter->get_datasource() . " (" . $critter->get_time() . ")-->\n";
            

        if ($separateDetailPages){
            $content .= "<a href=" . $utils->detailPageURL( $critter->get_id(),$critter->get_name()) . ">";
        }
        else {
            $content .= "<a href='?id=" . $critter->get_id() ."'>";
        }
            $content .= "<div class='pp_imageframe'><img class='pp_heroimage pp_". $critter->get_sex() . $accentclass . "' src='" ;

            if ($randomizephotos) {
                $availablePhotos = array($critter->get_photo1());
                $availablePhotos[] = $critter->get_photo1(); // second chance at photo1
                if (strlen($critter->get_photo2()) > 3) {$availablePhotos[] = $critter->get_photo2();}
                if (strlen($critter->get_photo3()) > 3) {$availablePhotos[] = $critter->get_photo3();}
                $photoURL = $availablePhotos[(rand(1,count($availablePhotos))-1)];
                $content .= $photoURL ;            
            }
            else {
                $content .= $critter->get_photo1();
            }
            $content .= "'>\n";
            
            $content .=  "<div class='pp_inlinecontent'><span class='pp_inlinename'>";
            if (strlen($critter->get_name()) > 16) { 
                $content .=  substr($critter->get_name(),0,14) ."...";
            }
            else {
                $content .=  $critter->get_name() ;
            }
            $content .= "</span>";

            if ($utils->optionChecked("sitename") || (array_key_exists("showsite",$attsIN))) {
                if (strlen($critter->get_sitename()) > 25) { 
                    $content .=  "<br>" . substr($critter->get_sitename(),0,23) ."...\n";
                }
                else {
                    $content .=  "<br>" . $critter->get_sitename() ."\n";
                }
                }

            $content .= "</div>";

            $stickiecount = 0;

            if ($utils->optionChecked("adoptionpending")){
                if ($critter->adoptionPending())
                {
                    $stickiecount ++;
                    $content .=  "<img src=\"" . plugin_dir_url(__FILE__) . "includes/images/adoption-pending.png\" class=\"pp_stickie" . $stickiecount . "-img\">";
                }
            }
            if ($utils->optionChecked("foster")){
                if ($critter->isInFoster())    {
                    $stickiecount ++;
                    $content .=  "<img src=\"" . plugin_dir_url(__FILE__) . "includes/images/foster.png\" class=\"pp_stickie" . $stickiecount . "-img\">";
                }
            }  
            if (petpress_sponsoredPet($critter->get_sitename(),$critter->get_price()))    {
                $stickiecount ++;
                $content .=  "<img src=\"" . plugin_dir_url(__FILE__) . "includes/images/sponsored-pet.png\" class=\"pp_stickie" . $stickiecount . "-img\">";
            }
            $content .= "</div></a>\n"; // end of one pet



            $weightclass = "unknown";

            if ($critter->get_species() == "Dog"){
                if ($cWeight > 0) { $weightclass = "xs";}
                if ($cWeight  >= 10) { $weightclass = "small";}
                if ($cWeight  >= 30) { $weightclass = "medium";}
                if ($cWeight  >= 60) { $weightclass = "large";}
                if ($cWeight  >= 100) { $weightclass = "xl";}
            }
            else{
                $weightclass = $critter->get_shortWeight();
            }

            $vitals = [];
            if (array_key_exists("sizeweight", $options) && ($options['sizeweight'] == 1)) {
                if (($critter->get_age()) != "" && ($critter->get_age() > 0)){
                    $vitals[] = $critter->approximateAge();
                }
                if ($critter->get_sex() != ""){
                    $vitals[] = strtolower($critter->get_sex());
                }
                if
                (($cWeight) != "" && ($cWeight > 0)) {
                    $vitals[] = $critter->get_shortWeight();
                }
            }
            else {
                if ($cAgegroup != "") {
                    $vitals[] = strtolower($cAgegroup);
                }
                if ($critter->get_sex() != "") {
                    $vitals[] = strtolower($critter->get_sex());
                }
                if ($weightclass != "") {
                    $vitals[] = $weightclass;
                }
            }

            $content .= implode(" | ", $vitals) . "<br>";

            if (strlen($cBreed) > 32) { 
                $content .=  substr($cBreed,0,30) . $videoicon . "...<br>\n";
            }
            else {
                $content .=  $cBreed .$videoicon ."<br>\n";
            }
            $content .= "<br>\n";

        $content .= ""; // desc stuff goes here
        $content .= "</div>\n";
    }
    }
    $content .= "</div>\n";

    $content .= petpress_makePaginationLinks($petsperpage, $numpages, $pagenum);

    $endpet --;
    if ($endpet > $count) {$endpet = $count;}
    $content .= "<div style='clear:both' id='pp_animalcount'>" . $startpet . " - " . $endpet . " of " . $count . " " . $speciesName . "s shown";

    //$options = get_option( 'petpress_plugin_options' );

    $content .= "<span";

    if ($options){
        if (array_key_exists("poweredby",$options) && ($options['poweredby'] == 1) ){
            $content .= " class='pp_poweredby'";
        }
        else {
            $content .= " class='pp_poweredby_disabled'";
        }
    }
    $content .= "> | Powered by <a href='https://www.airdriemedia.com/petpress'>PetPress</a></span>";

    $content .= "</div>\n";
    $content .= "</div>\n\n"; // end wrapper
    return $content;
}

function petpress_sponsoredPet($sitename,$INprice)
{
    if (($sitename == "Main Line Animal Rescue") || ($sitename == "MLAR")) {
        if ((substr($INprice, -3) == ".04") || (substr($INprice, -3) == ".05") || (substr($INprice, -3) == ".06") || (substr($INprice, -3) == ".07"))
        {
            return true;
        } 
    }
    return false;
}


function petpress_handle_request() {
    // Get requested URL
    $utils = new petpress_utilities_class();
    $options = get_option( 'petpress_plugin_options' );
    if (!$options) {
        return ""; // not set up yet
    }

    if($utils->getAuthKey($authKey) == false)
    {
        // Some error happened, output the error and be done.
        return ($authKey);
    }
    $atts = [];
    global $args;

    $request_uri = $_SERVER['REQUEST_URI'];

//   $pattern = '/\/petpress\/(\d+)\/([a-zA-Z]+)/';

   $pattern = '/\/pp(\d+)\/([a-zA-Z]+)/';


    $isDetailPageURL = true;

    if (preg_match($pattern, $request_uri, $matches)) {
        // Extract the number and string from the URL
        $id = $matches[1];
        $pet_name = $matches[2];
        
        if (!is_numeric($id)) {
            $isDetailPageURL = false;
        }

        if (!empty($string)) {
            $isDetailPageURL = false;
        }

        if ($isDetailPageURL) {

        $html = '<div id="primary" class="content-area"><main id="main" class="site-main" role="main">';
        $html .= petpress_showOneAnimal(htmlspecialchars($id), $atts, $authKey);
        $html .= ' </main><!-- #main --> ';
        $html .= '</div><!-- #primary -->';

        $critter = petpress_getOneAnimal($id,$authKey,false);

        $cName = $critter->get_name();
        $cSpecies = $critter->get_species();
        $cSitename = $critter->get_sitename();

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = $_SERVER['REQUEST_URI'];
        
        // Construct the full URL
        $current_url = $protocol . "://" . $host . $path;

        $shortDescription = $cSpecies . ' at ' . $cSitename;

        $socialdata  = '<meta property="og:title" content="Meet ' . $cName . '"/>';
        $socialdata .= '<meta property="og:description" content="' . $cName .' is an adoptable '. strtolower($critter->get_agegroup()) .' ' . $critter->get_breed() .' at ' . $cSitename.'"/>';
        $socialdata .= '<meta property="og:image" content="'.$critter->get_photo1().'">';
        $socialdata .= '<meta property="og:url" content="'. $current_url.'">';
        $socialdata .= '<meta property="og:type" content="website">';

        $socialdata .= '<meta name="twitter:card" content="'.$critter->get_photo1().'">';
        $socialdata .= '<meta name="twitter:title" content="Meet ' . $cName . '>"';
        $socialdata .= '<meta name="twitter:description" content="'. $cSpecies . ' at ' . $cSitename.'">';
        $socialdata .= '<meta name="twitter:image" content="'.$critter->get_photo1().'">';
        $socialdata .= '<meta name="twitter:url" content="'. $current_url.'">';

        $templateArgs ['theHTML'] = $html;
        $templateArgs ['pgTitle'] = 'Meet ' . $cName;
        $templateArgs ['socialdata'] = $socialdata;
        $templateArgs ['shortDesc'] = $shortDescription;

        load_template( plugin_dir_path(__FILE__) . '/templates/petpress-detail-template.php', true, $templateArgs);

        exit();
        }
    }
   
}

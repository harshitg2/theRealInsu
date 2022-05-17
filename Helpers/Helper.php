<?php
use App\Messages;
use App\ScheduleTask;
use App\UsersDetail;
use App\PolicyStatus;
use App\Agent;
use App\Policy;
use App\Clients;
use App\ClientType;
use App\ProspectType;
use App\Prospect;
use App\PaginationNumberShow;
use App\Phone;
use App\Http\Controllers\AgentController;
use App\TaskSubCategory;
use App\TaskStatus;

//use Session;

function getReceivedMessage(){
    $result= array();
    if(session('role')!='Admin'){
    
      $user_id=session('id');
      $agent_object = new AgentController;
      $agents_list=$agent_object->getMapAgentList($user_id);
      if(!empty($agents_list)){
        $result = Messages::where('type','in')->where('is_sms_viewed','false')
        ->where('user_id',session('id'))
        ->orwhereIn('servicing_agent',$agents_list)->orderBy('created_at','DESC')
        ->limit(10)->get();
      }else{
        $result = Messages::where('user_id',session('id'))->where('type','in')->orderBy('created_at','DESC')->limit(10)->get();
      }
    }else{
      $result = Messages::where('type','in')->where('is_sms_viewed','false')->orderBy('created_at','DESC')->limit(10)->get();
    }   
    return $result;
}

function getClientProspectNameRecievedSMS($sender_id,$sender_type=''){
  $name='';
  if($sender_type=='CLIENT'){
      
      $result = Clients::where('id',$sender_id)->first();
      if(!empty($result)){
        $name=$result->first_name." ".$result->last_name;  
      }
      
  }
  if($sender_type=='PROSPECT'){
    $result = Prospect::where('id',$sender_id)->first();
    if(!empty($result)){
       $name=$result->first_name." ".$result->last_name; 
    }
    
  }
  return $name;
}
 function getSMSCount(){
      $count = 0;
   if(session('role')=='Admin'){
     $count = Messages::where('type','in')->where('is_sms_viewed','false')->count();
   }else{

      $user_id=session('id');
      $agent_object = new AgentController;
      $agents_list=$agent_object->getMapAgentList($user_id);
      if(!empty($agents_list)){
        $count = Messages::where('type','in')->where('is_sms_viewed','false')
        ->where('user_id',session('id'))
        ->orwhereIn('servicing_agent',$agents_list)
        ->count();
      }else{
        $count = Messages::where('user_id',session('id'))->where('type','in')->where('is_sms_viewed','false')->count();
      }
   }
    
    return $count;
}
function dupFileChk($path,$filename,$i='0'){
  $filechk = $path.'/'.$filename;
  if (file_exists($filechk))
  {
    $new = explode(".",$filename);
    $new_line = rtrim($new['0'],"-Copy($i)."); 
    $i++;
    $new_filename =  $new_line."-Copy($i).".$new['1'];
    return dupFileChk($path,$new_filename,$i);
  } 
  else {
    return $filename;
  }     
 
}

function recordsExistCheck($policy_no,$policy_holder){

  $policy_name=explode(' ',$policy_holder);
  if(count($policy_name)==1){
    $policy_name[1]=str_replace("'",'', $policy_name[0]);
  }else{
    $policy_name[0]=str_replace("'",'', $policy_name[0]);
    $policy_name[1]=str_replace("'",'', $policy_name[1]);
  }
  
 $cli_query = DB::table('clients')->select('*')
 ->leftjoin('policies', 'clients.id', '=', 'policies.client_id')->whereRaw("(policies.policy_number LIKE '%$policy_no%') and (clients.first_name like '%$policy_name[0]%' or clients.last_name LIKE '%$policy_name[1]%')")->count();

 return $cli_query;
  
}
function ssnFormat($number = '') {
  if (!empty($number)) {
    $number = preg_replace('/[^A-Za-z0-9\-]/', '', trim($number));
    return preg_replace('/(\d{3})(\d{2})(\d{4})/', '$1-$2-$3', $number);
  }
}
function phoneFormat($number = '') {
  if (!empty($number)) {
    $number = str_replace(' ', '-', $number);
    $number = preg_replace('/[^A-Za-z0-9\-]/', '', $number);
    return preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '($1) $2-$3', $number);
  }
}

function digitsOnly($number = '') {
  if (!empty($number)) {
    $string = str_replace(' ', '', $number);
    $string = str_replace('-', '', $number);
    $number = preg_replace('/[^A-Za-z0-9\-]/', '', $string); 
  }
  return $number;
}

function dateFormat($date = '') {
  if(!empty($date)) {
    return date('m/d/Y', strtotime($date));
  }
}

function dateFormatWithHours($date=''){
  $now = Carbon\Carbon::now();
  $created_at = Carbon\Carbon::parse($date);
  $diffHuman = $created_at->format('m/d/Y h:i A');//$created_at->diffForHumans($now);
  return $diffHuman;
}
function strTotimedata($date=''){
  return strtotime($date);
}

function getPolicyColor($status = '', $from_colors = false)
{
  $color = 'Green';
  if ($from_colors) {
    $status = explode(',', $status);
    if (in_array('Pink', $status)) {
      $color = 'Pink';
    } elseif (in_array('Blue', $status) || in_array('#0c8fecf2', $status)) {
      $color = '#0c8fecf2';
    } elseif (in_array('Yellow', $status)) {
      $color = 'Yellow';
    }elseif (in_array('Green', $status)) {
      $color = 'Green';
    } elseif (in_array('Red', $status)) {
      $color = 'Red';
    } 
  } else {
    $policy_status = PolicyStatus::where('policy_id', $status)->first();
    if (!empty($policy_status->indicator)) {      
      $color = $policy_status->indicator;
    }
  } 
  return $color;
}
function getPolicyColorIndicator($color='') {
  $span = $title = 'Green';
  if ('Pink'==$color) {
    $span = '';
    $title = 'Pink';
  } elseif ('Blue'== $color || '#0c8fecf2'==$color) {
    $span = '#0c8fecf2';
    $title = 'Blue';
  } elseif ('Yellow'== $color) {
    $span = $title = 'Yellow';
  }elseif ('Red'== $color) {
    $span = $title = 'Red';    
  } 
  $span = "<span class='name-color' title='$title' style='background:$span'></span>&nbsp";
  return $span;
}
function getPolicyIndicator($status = '', $is_policy = '1') {
  $policy_status = PolicyStatus::select('indicator', DB::raw("group_concat(' ',policy_name) as title"))->groupBy('indicator')->get();
  $inc = [];
  foreach ($policy_status as $key => $pvalue) {
    $inc[$pvalue->indicator] = $pvalue->title; 
  }
  $status = explode(',', $status);
  $span = $title = 'Green';
  if (in_array('Pink', $status)) {
    $span = '';
    $title = $inc['Pink'];
  } elseif (in_array('Blue', $status) || in_array('#0c8fecf2', $status)) {
    $span = '#0c8fecf2';
    $title = $inc['Blue'];
  } elseif (in_array('Yellow', $status)) {
    $span = 'Yellow';
    $title = $inc['Yellow'];
  }elseif (in_array('Red', $status)) {
    $span = 'Red';
    $title =$inc['Red'];
  } 
  $span = "<span class='name-color' title='$title' style='background:$span'></span>&nbsp";
  return $span;
}

function generateTaskIndicator($is_task = '1') {
  $result = '';
  if ($is_task == '1') {
      $img = "<img src='".url('/new/images/task_icon.png')."' height='13px'>";
      //$result = "<span class='name-color' title='in-complete' style='background:cyan'></span>&nbsp";
      $result = "<span class='name-color1' title='in-complete'>??</span>&nbsp";

      $result = "<span class='name-color1' title='In-complete'><i class='fa fa-flag text-danger'> </i></span>&nbsp";
  }
  return $result;
}

function getTaskIndicator($type = '1', $client_id=''){
  $result = '';
  if (!empty($client_id)) {
    $where = ['client_id'=>$client_id, 'type'=>$type,'is_deleted'=>'false'];
    $flag_chk = TaskStatus::where(['is_active'=>'1','is_flag'=>'1'])->pluck('id')->toArray();
    $task_count = ScheduleTask::where($where)->whereIn('status',$flag_chk)->count();
    if($task_count>0){
      $result = "<span class='name-color1' title='New or Deffered'><i class='fa fa-flag text-danger'> </i></span>&nbsp";
    }    
  } else {
    $result = ScheduleTask::where('status', '<>', 'Complete')->where('type', $type)->groupBy('client_id');
    $result = $result->pluck('client_id')->toArray();
  }
  return $result;
}

function getProspectIndicator($status = '') {
  if (!empty($status)) {
    $status = "<img src='/images/image-$status.png' alt=' width='20' height='20'/>";
  }
  return $status;
}
/** Get Prospect Indicator based on the prospect ID**/
function getProspectIndicatorBasedOnId($prospect_id=''){
  $result='';
  if(!empty($prospect_id)){
    $indicator = Prospect::where('id',$prospect_id)->pluck('prospect_type')->first();
    $result=getProspectIndicator($indicator);
  }
  return $result;
}
/** End Here Prospect Indicator **/

/** Get Clients Indicator based on the Client ID**/
function getClientIndicatorBasedOnId($client_id=''){
  $result='';
  if(!empty($client_id)){
    $indicator = Clients::where('id',$client_id)->pluck('indicator')->first();
    $result=getPolicyIndicator($indicator);
  }
  return $result;
}
function getPolicyIndicatorBasedOnId($policy_id=''){
  $result='';
  if(!empty($policy_id)){
    $indicator = Policy::where('id',$policy_id)->pluck('indicator')->first();
    $result=getPolicyIndicator($indicator);
  }
  return $result;
}
/** End Here Clients Indicator **/

 /** get Notification count**/
 function getNotificationCount(){
      /*get current user which map with agent*/
       $count=0;
       $agent_id =array();
       if(session('role')=='Admin'){
          $count = ScheduleTask::where('status','!=','Complete')->where('is_deleted','false')->count();
       }else{
        $agent_id = getAgentID();
        $count = ScheduleTask::where('user_id',session('id'))->where('status','!=','Complete')->where('is_deleted','false')->count();
       }
       return $count;
 }
 
 /************return notification all data when login user is admin otherwise only agent data which mapp with login user********/
 function getNotificationData( $limit = '*'){

        $result=array();
        $agent_id =[];
        $task = DB::table('schedule_notification_task as t')->join('clients as c', 'c.id', 't.client_id');
        $task = $task->selectRaw('t.*,c.indicator,c.task_indicator,concat(c.first_name," ",c.last_name) as client_name,c.phone as phone_number,c.email');
        $task = $task->where('t.type', '1');
        $task = $task->where('t.status', '!=', 'Complete');
        $task = $task->where('t.is_deleted', 'false');

        $pro_task = DB::table('schedule_notification_task as t')->join('prospects as c', 'c.id', 't.client_id');
        $pro_task = $pro_task->selectRaw('t.*,c.prospect_type as indicator,c.task_indicator,concat(c.first_name," ",c.last_name) as client_name,c.phone as phone_number,c.email');
        $pro_task = $pro_task->where('t.type', '2');
        $pro_task = $pro_task->where('t.status', '!=', 'Complete');
        $pro_task = $pro_task->where('t.is_deleted', 'false');

        if(session('role')!='Admin'){
          $task = $task->where('t.user_id', session('id'));
          $pro_task = $pro_task->where('t.user_id', session('id'));
        }
        $result = $task->union($pro_task);
        if($limit!='*'){
          $result = $result->take($limit);
        }
        $result=$result->orderBy('new_dateTime','DESC')->limit('10')->get();
        return $result;
}

/** get Agent id which mapped with login user ******/
function getAgentID(){
        $agent_data =[];
        $login_id =session('id');
        $map_agent_name='';
        $map_agent_id ='';
        $agent_user_mapping =Agent::where('user_id',$login_id)->where(['is_agent'=>'1','agents_delete'=>'0','agents_active'=>'1'])->get();
        try{
        if(count($agent_user_mapping)>0){
          $map_agent_name = $agent_user_mapping[0]['agents_firstname'].''.$agent_user_mapping[0]['agents_laststname'];
          $map_agent_id   =  $agent_user_mapping[0]['agents_id'];
      }
        $agent_data['name'] = $map_agent_name;
        $agent_data['id']   = $map_agent_id;
        }catch(Exception $e){
           \Log::info($e->getMessage());
        }
     return $agent_data;  
}

function letterToNumber() {
    
  $letterNumberArr = [];
  $startLetter = 'A';
  $number = 1000;
  $counter = 0;

  while ($number-- > 0) {
    $letterNumberArr[$counter] = $startLetter;
    $counter++;
    $startLetter++;
  }

  return $letterNumberArr;
}

function getUrlParam() {
  $url = url()->full();
  $url = explode('?',$url);
  $URI = [];
  if (count($url)>1) {
    $query = $url[1];
    $values  = explode('&', $query);
    foreach ($values as $key => $value) {
      if (!strpos('&'.$value,'num')) {
        $URI[] = $value; 
      }
    }
  }
  return $url[0].'?'.implode('&', $URI).'&';
}

function getMessageFromTemplate($data = [], $temp = '') {
  if(count($data)>0 && !empty($temp)) {
    $keys   = array_keys($data);
    $values = array_values($data);
    $key    = array_map(function($value) { return '{'.$value.'}'; }, $keys);
    $temp   = str_replace($key, $values, $temp);
  }
  return $temp;
}

function getUserNameFromId($id = '') 
{
    if (empty($id)) {
      $users = UsersDetail::select(DB::raw("users_id,concat(users_firstname,' ',users_lastname) as Name"))->get()->toArray();
      $result = [];
      foreach ($users as $key => $value) {
        $result[$value['users_id']] = $value['Name'];
      }
      $users = $result;
    } else {
      $users = UsersDetail::select(DB::raw("users_id,concat(users_firstname,' ',users_lastname) as Name"))
              ->where('users_id', $id)->first();
    }
    return $users;
}

function makeComment($column = '') {
  return str_replace('$', '', str_replace('@', '', $column));
}
function getAgentName($id){
  $agent_name="";
  if(!empty($id)){
    $agents_name =Agent::where('agents_id',$id)->pluck('agents_name')->first();
    $agent_name=$agents_name;
  }
  return $agents_name;
}

function getUserName($id=''){
  $user_name='';
  if(!empty($id)){
    $user_details =UsersDetail::where('users_id',$id)->first();
    if($user_details){
        $first_name=$user_details['users_firstname'];
        $last_name=$user_details['users_lastname'];
        $user_name=$first_name." ".$last_name;
    }
  }

  return $user_name;
}

function getPagination() {
  $PaginationNumRows = PaginationNumberShow::orderBy('showNumpage','ASC')->get()->toArray();
  return $PaginationNumRows;
}

function createFolder($parent = 'images') {
    $new_folder  = $parent.'/'.date('Y_m').'/'.date('Y_m_d').'/';
    $upload_path = public_path() . '/'.$new_folder;
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0777, true);
    }
    return $new_folder;
}

function encryptId($id, $type = 'e') {
  return $id;
    // $hashids = new Hashids(' ',10);
    // if ($type=='e') {
    //     $return = $hashids->encode($id);
    // } else {
    //     $return = $hashids->decode($id);
    //     if (!empty($return[0])) {
    //         $return = $return[0];
    //     } else {
    //         $return = '';
    //     }
    // }
    // return $return;
}
function getImagesFromString($str){
  $img_data=[];
  $doc = new DOMDocument();
  $doc->loadHTML($str);
  $xml = simplexml_import_dom($doc);
  $images = $xml->xpath('//img');
  foreach ($images as $img)
  {
    $img_data[]=$img['src'];
  }
  return $img_data;
}


function clean($str)
{
  $str = utf8_decode($str);
  $str = str_replace("&nbsp;", "", $str);
  $str = preg_replace('/[\x00-\x1F\x7F-\xFF]/', ' ', $str);
  //$str = preg_replace("/\s+/", " ", $str);
  //$str = str_replace(array("<p>"), "\n\n", $str);
  $str = str_replace(array("<p></p>","<p><br></p>"),"", $str);
  $str = str_replace(array("</p>"), "\n", $str);
  //$str = trim($str);

  $str = strip_tags($str);
  return $str;
}
function getNotNullPolicyCount($client_id){
  $cli_query = DB::table('policies')->select('*')->where('policies.client_id',$client_id)->whereRaw("policies.is_deleted=0 and policies.policy_status is not null")->count();
  return $cli_query;
}
function getNullPolicyCount($client_id){
  $cli_query = DB::table('policies')->select('*')->where('policies.client_id',$client_id)->whereRaw("policies.is_deleted=0 and (policies.policy_status is null OR policies.policy_status='')")->count();
  return $cli_query;
}
function getClientypeName($id){
  $client_type_name=ClientType::where('id',$id)->pluck('client_type')->first();
  return $client_type_name;
}
function getProspectTypeName($id){
  $client_type_name=ProspectType::where('id',$id)->pluck('name')->first();
  return $client_type_name;
}
function showingDateFormat($str_date){
  $original_date = $str_date;

  // Creating timestamp from given date
  $timestamp = strtotime($original_date);

  // Creating new date format from that timestamp
  $new_date = date("m/d/Y", $timestamp);
  return $new_date;
}
function getMonthBetweenDate($st_date1,$st_date2){
  $date1 = $st_date1;
  $date2 = $st_date2;
  $d1=new DateTime($date2); 
  $d2=new DateTime($date1);                                  
  $Months = $d2->diff($d1); 
  return $howeverManyMonths = (($Months->y) * 12) + ($Months->m);
}
function getCommissionRate($month,$comp_id){
  $query=DB::table('payable_rates')
  ->where('rate_id',$comp_id)
  ->where('revenue_type','1')
  ->where('start_month','<=',$month)
  ->whereRaw("(end_month >=$month or end_month is null)")->first();
  return $query;
}

function getRateTypeName($id){
  $revenue_name=DB::table('comp_rate_types')
  ->where('id',$id)->pluck('rate_type_name')->first();
  return $revenue_name;

}
function getRevenueName($id){
  $revenue_name=DB::table('revenue_types')
  ->where('id',$id)->pluck('revenue_name')->first();
  return $revenue_name;
}
function getPayableMinMaxRate($rate_id){
  $min_val=0;
  $min_val=DB::table('payable_rates')
  ->select(DB::raw("MIN(payable_rate) AS min_rate"))
  ->where('rate_id',$rate_id)->pluck('min_rate')->first();
  return $min_val;
}
function getPayableMaxRate($rate_id){
  $max_val=0;
  $max_val=DB::table('payable_rates')
  ->select(DB::raw("MAX(payable_rate) AS max_rate"))
  ->where('rate_id',$rate_id)->pluck('max_rate')->first();
  return $max_val;
}
function getMergeSessionCount(){
  $num_merge_record=0;
  if(Session::has('merge_record_id'))
  {
    $arr_count=\Session::get('merge_record_id');
    $num_merge_record=count($arr_count);
     
  }
  return $num_merge_record;
}

function insertIntoPhonesTable($phoneArr , $type ,$type_id) {

  $data_phone=[];
  $res=Phone::where(['type_id'=>$type_id , 'type'=>$type])->delete();
  $phoneArr = array_map("digitsOnly" , $phoneArr);
  foreach ($phoneArr as $key => $phn_value) {
      # code...
      if(is_null($phn_value)){
        continue;
      }
      $data_phone[$key]['phone_number']=$phn_value;
      $data_phone[$key]['type_id']=$type_id;
      $data_phone[$key]['type']=$type;
      $data_phone[$key]['is_deleted']=0;
      $data_phone[$key]['created_by']=empty(session('id'))?'0':session('id');
      $data_phone[$key]['updated_by']=empty(session('id'))?'0':session('id');
  }
  Phone::insert($data_phone);

}

function formatPhoneForClientsTable($phone_arr) {
  $phoneArr = array_map("digitsOnly" , $phone_arr );
  $result = implode(',', $phoneArr);
  return $result;
}
function getCommonClientProspectName($client_id,$type){
  $message='';
  if($type=='1'){
    $result=Clients::where('id',$client_id)->first();
    if(isset($result))
    {
      $message=" for client name - <b><a href='/Individual/".$client_id."'>".$result->first_name." ".$result->last_name."</a> </b> and client Id -  <b><a href='/Individual/".$client_id."'>".$client_id."</a></b>";
    }
  }
  elseif($type=='2')
  {
    $result=Prospect::where('id',$client_id)->first();
    if(isset($result))
    {
      $message=" for prospect name - <b> <a href='/updateProsp/".$client_id."'>".$result->first_name." ".$result->last_name."</a></b> and prospect Id - <b><a href='/updateProsp/".$client_id."'>".$client_id."</a></b>";
    }
  }
  elseif($type=='3'){
    $result=Agent::where('agents_id',$client_id)->first();
    if(isset($result))
    {
      $message=" for agent name - <b> <a href='/Agent/".$client_id."'>".$result->agents_firstname." ".$result->agents_lastname."</a></b> and agent Id - <b><a href='/Agent/".$client_id."'>".$client_id."</a></b>";
    }
  }
  return $message;
}
function getDatefromToday(){
  $legalAge = date('m/d/Y', strtotime('-18 year'));
  return $legalAge;
}
function getDefaultServicingAgent(){
  if(session('Staff_Type')=='1')
  {
    $agent = session('AGENT');
    $agent_id = session('AGENT_ID');
  }
  else
  {
    $agent = Config::get('constants.DEFAULT_AGENT_NAME'); 
    $agent_id = Config::get('constants.DEFAULT_AGENT_ID');
  }
  return [$agent,$agent_id];
  //return compact($agent,$agent_id);        
}
function managerChk($user_id){  
  $manager = DB::select("select team_id from tbl_teams where (managers like '%,$user_id,%' or managers like '$user_id,%' or managers like '%,$user_id' or managers='$user_id') and is_deleted='0'");
  if(!empty($manager))
  {
    return true;
  }
  else
  {
    return false;
  }        
}
function getManagerTeam($user_id)
{
  $total_users=array();
  $manager = DB::select("select team_id,managers from tbl_teams where (managers like '%,$user_id,%' or managers like '$user_id,%' or managers like '%,$user_id' or managers='$user_id') and is_deleted='0'");
  if(empty($manager))
  {
    array_push($total_users,$user_id);
  }
  else
  {
    foreach ($manager as  $value) {
        if(in_array(session('id'),explode(",",$value->managers)))
        {
          $manager_chk="1";
          $users = DB::select("select users_id from tbl_users_detail where (users_teams like '%,$value->team_id,%' or users_teams like '$value->team_id,%' or users_teams like '%,$value->team_id' or users_teams='$value->team_id') and is_deleted='0' and users_active='1'");
          if(in_array($user_id, array_column($users, 'users_id')))
          {
            foreach ($users as $value) {
              array_push($total_users,$value->users_id);
            }  
          }
          
        }               
    } 
  }
  return $total_users;     
}
function getTaskCategoryName($sub_catId)
{
  $cat_name = TaskSubCategory::where('id',$sub_catId)->where('is_deleted','0')->pluck('subcat_name')->first();
  return $cat_name;
}
function geteamUser($team_id)
{  
   $manager = UsersDetail::whereRaw("(users_teams like '%,$team_id,%' or users_teams like '$team_id,%' or users_teams like '%,$team_id' or users_teams='$team_id') and is_deleted='0' and users_active='1'")->get()->pluck('users_id')->toArray();
  return $manager;     
}
function getTaskStatusName($status_id)
{
  $cat_name = TaskStatus::where('id',$status_id)->pluck('task_status')->first();
  return $cat_name;
}

function getPolicyNumberByCarrierCoverageId($carrier_id,$coverage_type,$client_id,$id=''){

  try {
        $response = ['is_available'=>false, 'total'=>'0','policy_number'=>''];
        if (!empty($carrier_id) && !empty($client_id) && !empty($coverage_type)) {
            $where = ['carrier_id'=>$carrier_id, 'client_id'=>$client_id,'coverage_type'=>$coverage_type,'is_deleted'=>'0'];
            $policy = Policy::where($where)->where('policy_number','!=','');

            if(!empty($id)){
               $policy = $policy->where('id', '<>', $id); 
            }
            $policy = $policy->orderBy('id','DESC')->pluck('policy_number');
            $total = $policy->count();             
            
            if ($total > 0) {
                $response = [
                    'is_available'=>true, 
                    'total'=>$total,
                    'policy_number'=>$policy->first()
                ];
            }
        }
        return json_encode($response,true);
    } catch (Exception $e) {
        Log::info($e->getMessage());
    }
}
function getPercentage($value,$total)
{
  if(!empty($value))
  {
    $task_percentage = ($value/$total) * 100;
    return round($task_percentage,'2');
  }
  else
  {
    return 0;        
  }
}      
    

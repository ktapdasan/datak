<?php
require_once('../../CLASSES/ClassParent.php');
class Employees extends ClassParent 
{
    var $pk = NULL;
    var $employee_id = NULL;
    var $first_name = NULL;
    var $middle_name = NULL;
    var $last_name = NULL;
    var $email_address = NULL;
    var $business_email_address = NULL;
    var $titles_pk = NULL;
    var $levels_pk = NULL;
    var $department = NULL;
    var $date_created = NULL;
    var $archived = NULL;

    public function __construct(
                                    $pk,
                                    $employee_id,
                                    $first_name,
                                    $middle_name,
                                    $last_name,
                                    $email_address,
                                    $business_email_address,
                                    $titles_pk,
                                    $levels_pk,
                                    $department,
                                    $date_created,
                                    $archived
                                )
    {
        
        $fields = get_defined_vars();
        
        if(empty($fields)){
            return(FALSE);
        }

        //sanitize
        foreach($fields as $k=>$v){
            if(is_array($v)){
                foreach($v as $key=>$value){
                    $v[$key] = pg_escape_string(trim(strip_tags($value)));
                }
                $this->$k = $v;
            }
            else {
                $this->$k = pg_escape_string(trim(strip_tags($v)));    
            }
        }

        return(true);
    }

    public function auth($post){
        $empid = pg_escape_string(strip_tags(trim($post['empid'])));
        $password = pg_escape_string(strip_tags(trim($post['password'])));

        $sql = <<<EOT
                select 
                    employees.*
                from accounts
                left join employees on (accounts.employee_id = employees.employee_id)
                where employees.archived = false
                and accounts.employee_id = '$empid'
                and accounts.password = md5('$password')
                ;
EOT;

        return ClassParent::get($sql);
    }

    public function fetch($post){
        $title = pg_escape_string(strip_tags(trim($post['title'])));

        $sql = <<<EOT
                with A as
                (
                    select 
                        employees.pk,
                        employees.employee_id,
                        employees.first_name,
                        employees.middle_name,
                        employees.last_name,
                        employees.email_address,
                        employees_titles.titles_pk,
                        employees.details->'company'->'work_schedule' as work_schedule
                    from employees
                    left join employees_titles on (employees.pk = employees_titles.employees_pk)
                    where employees.archived = false
                    order by last_name, first_name
                )
                select
                    *
                from A
                ;
EOT;

        return ClassParent::get($sql);
    }

    public function fetch_for_timesheet($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        $and="";
        if($data['employees_pk'] && $data['employees_pk'] != "undefined"){
            $and = "and employees.pk = " . $data['employees_pk'];
        }
        
        $sql = <<<EOT
                with A as
                (
                    select 
                        employees.pk,
                        employees.employee_id,
                        employees.first_name,
                        employees.middle_name,
                        employees.last_name,
                        employees.email_address,
                        employees_titles.titles_pk,
                        employees.details->'company'->'work_schedule' as work_schedule
                    from employees
                    left join employees_titles on (employees.pk = employees_titles.employees_pk)
                    where employees.archived = false
                    $and
                    order by last_name, first_name
                )
                select
                    *
                from A
                ;
EOT;

        return ClassParent::get($sql);
    }

    public function fetch_all($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }
        $str=$data['searchstring'];
        $lvl=$data[levels_pk];
        $dept=$data[departments_pk];
        $posi=$data[titles_pk];
        $where = "";

        if ($str){
            $where .= " AND (first_name ILIKE '$str%' OR middle_name ILIKE '$str%' 
                OR last_name ILIKE '$str%' OR employee_id ILIKE '$str%' )";
        }

        if($lvl){
            $where.=" AND levels_pk = '$lvl'";
        }
        if($dept){
            $where.=" AND department = '{{$dept}}'";
        }
        if($posi){
            $where.=" AND titles_pk = '$posi'";
        }

        $status = $data['status'];
        if ($status){
            if ($status == 'Active'){
                $status = 'false';
            }
            else {
                $status = 'true';
            }
            $where .= " AND employees.archived = $status";
        }

        $sql = <<<EOT
                select 
                    pk,
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    email_address,
                    business_email_address,
                    groupings.supervisor_pk as supervisor_pk,
                    (select first_name||' '||last_name from employees where pk = groupings.supervisor_pk)
                    as supervisor,
                    titles_pk,
                    (select title from titles where pk = employees.titles_pk) as title,
                    levels_pk,
                    (select level_title from levels where pk = employees.levels_pk) as level,
                    array_to_string(department, ',') as departments_pk,
                    department as departments_pk_arr,
                    (select array_to_string(array_agg(department), ', ') from departments where pk = any(employees.department)) as department,
                    date_created,
                    details, 
                    employees.archived,
                    employees_permissions.permission
                from employees
                left join groupings on (groupings.employees_pk = employees.pk)
                left join employees_permissions on (employees_permissions.employees_pk = employees.pk)
                where true
                $where
                order by date_created
                ;
EOT;

        return ClassParent::get($sql);
    }

    public function profile(){
        $sql = <<<EOT
                select 
                    pk,
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    email_address,
                    employees_permissions.permission,
                    (select supervisor_pk from groupings where employees_pk = pk) as supervisor_pk,
                    details,
                    leave_balances
                from employees
                left join employees_permissions on (employees.pk = employees_permissions.employees_pk)
                where employees.archived = false
                and md5(pk::text) = '$this->pk'
                ;
EOT;

        return ClassParent::get($sql);
    }

    public function last_log($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        $pk = $data['pk'];
        $sql = <<<EOT
                select 
                    employees_pk,
                    type,
                    time_log::date as date,
                    time_log::time(0) as time,
                    date_created
                from time_log
                where employees_pk = $pk
                order by time_log desc limit 1
                ;
EOT;

        return ClassParent::get($sql);   
    }

    public function log_today($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        $today = date('Y-m-d');
        $pk = $data['pk'];
        $sql = <<<EOT
                select 
                    employees_pk,
                    type,
                    time_log::date as date,
                    time_log::time(0) as time,
                    date_created,
                    random_hash
                from time_log
                where employees_pk = $pk
                and time_log::date = '$today'
                order by date_created desc limit 1
                ;
EOT;

        return ClassParent::get($sql);   
    }

    public function manual_log($data)
    {
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

       
        $time_log=$data['time_log'];
        $reason=$data['reason'];
        $date_log=$data['date_log'];
        $employees_pk=$data['employees_pk'];
        $supervisor_pk=$data['supervisor_pk'];
        $type=$data['type'];

        $sql = "begin;";
        $sql .= <<<EOT
                insert into manual_logs(
                    employees_pk,
                    time_log,
                    date_created
                )
                values
                (
                    $employees_pk,
                    $time_log,
                    $date_log
                )
EOT;


    }

    public function submit_log($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        if($data['type'] == 'In'){
            $random_hash = $this->generateRandomString(50);    
        }
        else {
            $random_hash = $data['random_hash'];
        }
        

        $pk = $data['employees_pk'];
        $type = $data['type'];

        $sql = <<<EOT
                insert into time_log
                (
                    employees_pk,
                    type,
                    random_hash
                )
                values
                (
                    $pk,
                    '$type',
                    '$random_hash'
                )
                ;
EOT;

        return ClassParent::insert($sql);
    }

    private function generateRandomString($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMOPQRSTUVWXYZ_-';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function timesheet($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        $datefrom = $data['newdatefrom'];
        $dateto = $data['newdateto'];
        $pk = $data['pk'];

        $sql = <<<EOT
                with Q as
                (
                    select
                        employees_pk,
                        (select employee_id from employees where pk = employees_pk) as employee_id,
                        (select first_name ||' '|| middle_name ||' '|| last_name from employees where pk = employees_pk) as employee,
                        type,
                        time_log::date as log_date,
                        time_log::time(0) as log_time,
                        date_created
                    from time_log
                    where employees_pk = $pk
                    and time_log::date between '$datefrom' and '$dateto'
                )
                select
                    employees_pk,
                    employee_id,
                    employee,
                    log_date,
                    to_char(log_date, 'Day') as log_day,
                    (
                        coalesce((select
                            min(log_time)
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'In')::text,'None')
                    ) as login,
                    (
                        coalesce((select
                            min(log_time)
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'Out')::text,'None')
                    ) as logout,
                    coalesce(((
                        select
                            min(log_time)
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'Out'
                    ) -
                    (
                        select
                            min(log_time)
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'In'
                    ))::text,'N/A') as hrs
                from Q as logs
                group by employees_pk, employee, employee_id, log_date, log_day
                order by logs.log_date, employee
                ;
EOT;

        return ClassParent::get($sql);
    }

    public function employees($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        $employeeNumber = $data['employeeNumber'];
        $EmployeeFN = $data['dateto'];
        $pk = $data['pk'];

$sql = <<<EOT
                update accounts set
                (password)
                =
                ('$password')
                where employee_id = '$employee_id'
                and password = md5('$old_password')
                ;
EOT;

        return ClassParent::get($sql);
    } 

    public function timelogs($data){

        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        // $lvl=$data['levels_pk'];
        // $dept=$data['departments_pk'];
        // $posi=$data['titles_pk'];
        $where = "";

        if($data['employees_pk'] && $data['employees_pk'] != 'undefined'){
            $where .= "and employees_pk = ". $data['employees_pk']; 
        }

        
        /* if($data['departments_pk']){
            $where .= "and departments_pk = ". $data['departments_pk']; 
        }*/
        // if($lvl){
        //     $where.=" AND levels_pk = '$lvl'";
        // }else{
        //     $where.="";
        // }
        // if($dept){
        //     $where.=" AND department = '{{$dept}}'";
        // }else{
        //     $where.="";
        // }
        // if($posi){
        //     $where.=" AND titles_pk = '$posi'";
        // }else{
        //     $where.="";
        // }

        $datefrom = $data['newdatefrom'];
        $dateto = $data['newdateto'];

        $sql = <<<EOT
                with Q as
                (
                    select
                        employees_pk,
                        (select employee_id from employees where pk = employees_pk) as employee_id,
                        (select last_name ||', '|| first_name ||' '|| middle_name from employees where pk = employees_pk) as employee,
                        type,
                        time_log::date as log_date,
                        time_log::time(0) as log_time,
                        employees.details->'company'->'work_schedule' as work_schedule
                    from time_log
                    left join employees on (employees.pk = time_log.employees_pk)
                    where time_log::date between '$datefrom' and '$dateto'
                    $where
                )
                select
                    employees_pk,
                    employee_id,
                    employee,
                    work_schedule,
                    log_date,
                    to_char(log_date, 'dd-Mon-YYYY') as log_date2,
                    to_char(log_date, 'Day') as log_day,
                    (
                        coalesce((select
                            min(log_time)
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'In')::text,'None')
                    ) as log_in,
                    (
                        coalesce((select
                            min(log_time)
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'Out')::text,'None')
                    ) as log_out,
                    coalesce(((
                        select
                            min(log_time)
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'Out'
                    ) -
                    (
                        select
                            min(log_time)
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'In'
                    ))::text,'N/A') as hrs
                from Q as logs
                group by employees_pk, employee, employee_id, log_date, work_schedule
                order by logs.log_date,logs.employee
                ;
EOT;

        return ClassParent::get($sql);
    }

  
    public function employeelist($data){
       foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        $str=$data['searchstring'];
        $lvl=$data[levels_pk];
        $dept=$data[departments_pk];
        $posi=$data[titles_pk];
        $where = "";
        if ($str){
            $where .= " AND (first_name ILIKE '$str%' OR middle_name ILIKE '$str%' 
                OR last_name ILIKE '$str%' OR employee_id ILIKE '$str%' )";
        }

        if($lvl){
            $where.=" AND levels_pk = '$lvl'";
        }else{
            $where.="";
        }
        if($dept){
            $where.=" AND department = '{{$dept}}'";
        }else{
            $where.="";
        }
        if($posi){
            $where.=" AND titles_pk = '$posi'";
        }else{
            $where.="";
        }


        $status = $data['status'];
        if ($status){
            if ($status == 'Active'){
                $status = 'false';
            }
            else {
                $status = 'true';
            }
            $where .= " AND archived = $status";
        }

        $sql = <<<EOT
                
                select
                    
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    email_address,
                    business_email_address,
                    titles_pk,
                    (select title from titles where pk = employees.titles_pk) as title,
                    levels_pk,
                    (select level_title from levels where pk = employees.levels_pk) as level,
                    array_to_string(department, ',') as departments_pk,
                    department as departments_pk_arr,
                    (select array_to_string(array_agg(department), ' & ') from departments where pk = any(employees.department)) as department  
                from employees
                where true
                $where
                order by date_created
                ;
EOT;

        return ClassParent::get($sql);
    }

    public function change_password($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        $employee_id = $data['employee_id'];
        $old_password = $data['old_password'];
        $password = md5($data['new_password']);

        $sql = <<<EOT
                update accounts set
                (password)
                =
                ('$password')
                where employee_id = '$employee_id'
                and password = md5('$old_password')
                ;
EOT;

        return ClassParent::update($sql);
    }

    public function submit_comment($data){
        foreach($data as $k=>$v){
            $data[$k] = pg_escape_string(trim(strip_tags($v)));
        }

        $tool = $data['tool'];
        $feedback = $data['feedback'];

        $sql = <<<EOT
                insert into feedbacks
                (
                    feedback,
                    tool
                )
                values
                (
                    '$feedback',
                    '$tool'
                );
EOT;

        return ClassParent::insert($sql);
    }

    public function create($extra){
        foreach($extra as $k=>$v){
            if(is_string($v)){
                $extra[$k] = pg_escape_string(trim(strip_tags($v)));    
            }
            else {
                $extra[$k] = $v;
            }
        }

        $this->department = "{".$this->department."}";

        $array = $extra['details'];
        $details = json_encode($array);

        $sql = "begin;";
        $sql .= <<<EOT
                insert into employees
                (
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    business_email_address,
                    email_address,
                    titles_pk,
                    department,
                    levels_pk,
                    details
                )
                values
                (
                    '$this->employee_id',
                    '$this->first_name',
                    '$this->middle_name',
                    '$this->last_name',
                    '$this->business_email_address',
                    '$this->email_address',
                    '$this->titles_pk',
                    '$this->department',
                    '$this->levels_pk',
                    '$details'

                );
EOT;
        $sql .= <<<EOT
                insert into accounts
                (
                    employee_id,
                    password
                )
                values
                (
                    '$this->employee_id',
                    md5('user123456')
                );
EOT;
        $supervisor_pk = $extra['supervisor_pk'];
        $sql .= <<<EOT
                insert into groupings
                (   
                    employees_pk,
                    supervisor_pk
                )
                values
                (
                    currval('employees_pk_seq'),
                    $supervisor_pk
                )
                ;
EOT;

        $sql .= "commit;";

        return ClassParent::insert($sql);
    }

    public function update_employees($extra){
        foreach($extra as $k=>$v){
            if(is_string($v)){
                $extra[$k] = pg_escape_string(trim(strip_tags($v)));    
            }
            else {
                $extra[$k] = $v;
            }
        }

        
 
        $department = '{' . $this->department . '}';
        $details = json_encode($extra['details']);
        // $personal = json_encode($extra['personal']);

        $sql = "begin;";
        $sql .= <<<EOT
                UPDATE employees set
                (
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    business_email_address,
                    email_address,
                    titles_pk,
                    department,
                    levels_pk,
                    details
                )
                =
                (
                    '$this->employee_id',
                    '$this->first_name',
                    '$this->middle_name',
                    '$this->last_name',
                    '$this->business_email_address',
                    '$this->email_address',
                    $this->titles_pk,
                    '$department',
                    $this->levels_pk,
                    '$details'
                )
                where pk = $this->pk
                ;

EOT;
        $sql .= <<<EOT
                delete from groupings 
                where employees_pk = $this->pk
                ;
EOT;

        $supervisor_pk = $extra['supervisor_pk'];
        $sql .= <<<EOT
                insert into groupings
                (   
                    employees_pk,
                    supervisor_pk
                )
                values
                (
                    $this->pk,
                    $supervisor_pk
                )
                ;
EOT;

        $sql .= "commit;";

        return ClassParent::update($sql);
    }

    public function deactivate($info,$extra){

        foreach($extra as $k=>$v){
            $extra[$k] = pg_escape_string(trim(strip_tags($v)));
        }  
        $employees_pk = $this->employees_pk;
        $supervisor_pk = $extra['supervisor_pk'];
        $created_by = $extra['created_by'];
        $hr=json_encode($info);

        
        $sql = 'begin;';

        $sql .=<<<EOT
                insert into attritions
                (
                    employees_pk,
                    hr_details
                )
                values
                (
                    $this->pk,
                    '$hr'
                );
EOT;

       

        $sql .= <<<EOT
                insert into notifications
                (   
                    notification,
                    table_from,
                    table_from_pk,
                    employees_pk,
                    created_by
                )
                values
                (    
                    'New attrition filed',
                    'attritions',
                    currval('attritions_pk_seq'),
                    $supervisor_pk,
                    $created_by
                )
                ;
EOT;

        $sql .= "commit;";

        return ClassParent::update($sql);
    }

    public function reactivate(){

        $sql = <<<EOT
                update employees
                set archived = False
                where pk = $this->pk;
EOT;
        return ClassParent::update($sql);
    }


    public function get_supervisors(){

        $sql = <<<EOT
            select 
                pk,
                first_name||' '|| last_name as name
            from employees
            where levels_pk != 3 and
                levels_pk != 7
            ;
EOT;

        return ClassParent::get($sql);
    }

    public function get_permissions(){
        $sql = <<<EOT
                select 
                    permission 
                from where employees_pk = $this->pk
                ;
EOT;

        return ClassParent::get($sql);
    }


    public function update_permissions($data){
        foreach($data as $k=>$v){
            if(is_array($v)){
                foreach($v as $key=>$value){
                    $v[$key] = pg_escape_string(trim(strip_tags($value)));
                }
                $data[$k] = $v;
            }
            else {
                $data[$k] = pg_escape_string(trim(strip_tags($v)));
            }
        }

        $data = json_encode($data);
        $sql = "begin;";
        $sql .= <<<EOT
                delete from employees_permissions where employees_pk = $this->pk;
EOT;

        $sql .= <<<EOT
                insert into employees_permissions
                (
                    employees_pk,
                    permission
                ) 
                values
                (
                    $this->pk,
                    '$data'
                );
EOT;
        $sql .= "commit;";

        return ClassParent::update($sql);
    }
//      public function open_manual_log($data){
//        foreach($data as $k=>$v){
//             $data[$k] = pg_escape_string(trim(strip_tags($v)));
//         }

//         $log_time = $data['log_time'];
//         $log_reason= $data['log_reason'];

//         $sql = <<<EOT
//                 insert into time_log
//                 (
//                     log_time,
//                     log_reason
//                 )
//                 values
//                 (
//                     '$log_time',
//                     '$log_reason'
//                 )
//                 ;
// EOT;

//         return ClassParent::insert($sql);   

  
        
}

/*
select
                            case when logs.type = 'In' then
                            min(log_time)
                            else max(log_time) end
                        from Q where Q.employees_pk = logs.employees_pk
                        and Q.log_date = logs.log_date and Q.type = 'In'
                    ) as log_time,
*/

?>
app.controller('Leave', function(
                                        $scope,
                                        SessionFactory,
                                        EmployeesFactory,
                                        LevelsFactory,
                                        LeaveFactory,
                                        ngDialog,
                                        UINotification,
                                        md5
                                    ){

    $scope.pk='';
    $scope.leave_types={};
    $scope.profile= {};

    $scope.filter= {};
    $scope.filter.status= "Active";

    $scope.modal = {};

    $scope.leaves_filed = {};

    $scope.myemployees={};
   


    init();

    function init(){
        var promise = SessionFactory.getsession();
        promise.then(function(data){
            var _id = md5.createHash('pk');
            $scope.pk = data.data[_id];

            leavetypes();
            get_profile();
            leaves_filed();
            
           


            
        });
    }

    function get_profile(){
        var filters = { 
            'pk' : $scope.pk
        };

        var promise = EmployeesFactory.profile(filters);
        promise.then(function(data){
            $scope.profile = data.data.result[0];
            DEFAULTDATES();
            fetch_myemployees(); 
        })  

         
    } 


    
    $scope.add_leave = function(k){
    leaves_filed();
    get_profile();
    $scope.modal.reason = '';
    $scope.modal.date_started = new Date;
    $scope.modal.date_ended = new Date;

    $scope.modal = {

        title : 'File for Leave',
        save : 'Submit',
        close : 'Cancel'

    };

    ngDialog.openConfirm({
        template: 'SubmitLeaveModal',
        className: 'ngdialog-theme-plain custom-widththreefifty',
        preCloseCallback: function(value) {
            var nestedConfirmDialog;

            
                nestedConfirmDialog = ngDialog.openConfirm({
                    template:
                            '<p></p>' +
                            '<p>Are you sure you want file leave?</p>' +
                            '<div class="ngdialog-buttons">' +
                                '<button type="button" class="ngdialog-button ngdialog-button-secondary" data-ng-click="closeThisDialog(0)">No' +
                                '<button type="button" class="ngdialog-button ngdialog-button-primary" data-ng-click="confirm(1)">Yes' +
                            '</button></div>',
                    plain: true,
                    className: 'ngdialog-theme-plain custom-widththreefifty'
                });

            return nestedConfirmDialog;
        },
        scope: $scope,
        showClose: false
    })
    .then(function(value){
        return false;
    }, function(value){

        var date_started= new Date($scope.modal.date_started);
            var dd = date_started.getDate();
            var mm= date_started.getMonth();
            var yyyy = date_started.getFullYear();
        var date_ended= new Date($scope.modal.date_ended);
            var DD= date_ended.getDate();
            var MM = date_ended.getMonth(); 
            var YYYY = date_ended.getFullYear(); 
           
        $scope.modal.date_started = yyyy+'-'+mm+'-'+dd;
        $scope.modal.date_ended = YYYY+'-'+MM+'-'+DD;
        $scope.modal["employees_pk"] = $scope.profile.pk;
        $scope.modal["supervisor_pk"] = $scope.profile.supervisor_pk;

        
        var promise = LeaveFactory.add_leave($scope.modal);
        promise.then(function(data){
            UINotification.success({
                                    message: 'You have successfully filed leave.', 
                                    title: 'SUCCESS', 
                                    delay : 5000,
                                    positionY: 'top', positionX: 'right'
                                });
           
            leaves_filed();
        })
        .then(null, function(data){
            
            UINotification.error({
                                    message: 'An error occured, unable to save changes, please try again.', 
                                    title: 'ERROR', 
                                    delay : 5000,
                                    positionY: 'top', positionX: 'right'
                                });
        });         

       });                   
    }

    $scope.show_leavetypes = function(){
        leavetypes();
    }
   

    function leavetypes(){



        $scope.leave_types.status = false;
        $scope.leave_types.data= '';
        
        if ($scope.filter.status == 'Active')
        {
            $scope.filter.archived = 'false';  
        }
        else 
        {
            $scope.filter.archived = 'true';   
        }
        
        var promise = LeaveFactory.get_leavetypes($scope.filter);
        promise.then(function(data){
            $scope.leave_types.status = true;
            $scope.leave_types.data = data.data.result;

        })
        .then(null, function(data){
            $scope.leave_types.status = false;
        });
    }


    


    function leaves_filed() {

       
        var promise = LeaveFactory.leaves_filed($scope.filter);
        promise.then(function(data){
            $scope.leaves_filed.status = true;
            $scope.leaves_filed.data = data.data.result;
        })
        .then(null, function(data){
            $scope.leaves_filed.status = false;
        }); 
    }

    
    $scope.show_approve = function(k){
       $scope.leaves_filed["employees_pk"] = $scope.profile.pk;
       $scope.modal = {
                title : '',
                message: 'Are you sure you want to approve leave filed?',
                save : 'Yes',
                close : 'Cancel'

            };
        ngDialog.openConfirm({
            template: 'ConfirmModal',
            className: 'ngdialog-theme-plain',
            
            scope: $scope,
            showClose: false
        })
        
        .then(function(value){
            return false;
        }, function(value){

            $scope.leaves_filed.status = "Approved";
            $scope.leaves_filed.pk =  $scope.leaves_filed.data[k].pk;
  
            var promise = LeaveFactory.approve($scope.leaves_filed);
            promise.then(function(data){

                UINotification.success({
                                        message: 'You have successfully approve manual log.', 
                                        title: 'SUCCESS', 
                                        delay : 5000,
                                        positionY: 'top', positionX: 'right'
                                    });  
                leaves_filed();    

            })
            .then(null, function(data){
                
                UINotification.error({
                                        message: 'An error occured, unable to approve, please try again.', 
                                        title: 'ERROR', 
                                        delay : 5000,
                                        positionY: 'top', positionX: 'right'
                                    });
            });                                  
        });
    }

    $scope.show_disapprove = function(k){
        $scope.leaves_filed["employees_pk"] = $scope.profile.pk;
        $scope.modal = {
                title : '',
                message: 'Are you sure you want to disapprove leave filed?',
                save : 'Yes',
                close : 'Cancel'

            };
        ngDialog.openConfirm({
            template: 'ConfirmModal',
            className: 'ngdialog-theme-plain',
            
            scope: $scope,
            showClose: false
        })
        
        .then(function(value){
            return false;
        }, function(value){

            $scope.leaves_filed.status = "Disapproved";
            $scope.leaves_filed.pk =  $scope.leaves_filed.data[k].pk;

            
            var promise = LeaveFactory.disapprove($scope.leaves_filed);
            promise.then(function(data){

                UINotification.success({
                                        message: 'You have successfully diapproved leave filed.', 
                                        title: 'SUCCESS', 
                                        delay : 5000,
                                        positionY: 'top', positionX: 'right'
                                    });  
                leaves_filed();
            })
            .then(null, function(data){
                
                UINotification.error({
                                        message: 'An error occured, unable to disapprove, please try again.', 
                                        title: 'ERROR', 
                                        delay : 5000,
                                        positionY: 'top', positionX: 'right'
                                    });
            });                                  
        });
    }

    $scope.edit_leavetype = function(k){

    $scope.modal = {

        title : 'Edit Leave Type',
        save : 'Apply Changes',
        close : 'Cancel',
            name: $scope.leave_types.data[k].name,
            days: $scope.leave_types.data[k].days,
            code: $scope.leave_types.data[k].code,
            pk: $scope.leave_types.data[k].pk
    };

    ngDialog.openConfirm({
        template: 'LeaveTypeModal',
        className: 'ngdialog-theme-plain custom-widththreefifty',
        preCloseCallback: function(value) {
            var nestedConfirmDialog;

            
                nestedConfirmDialog = ngDialog.openConfirm({
                    template:
                            '<p></p>' +
                            '<p>Are you sure you want to apply changes to this leave type?</p>' +
                            '<div class="ngdialog-buttons">' +
                                '<button type="button" class="ngdialog-button ngdialog-button-secondary" data-ng-click="closeThisDialog(0)">No' +
                                '<button type="button" class="ngdialog-button ngdialog-button-primary" data-ng-click="confirm(1)">Yes' +
                            '</button></div>',
                    plain: true,
                    className: 'ngdialog-theme-plain custom-widththreefifty'
                });

            return nestedConfirmDialog;
        },
        scope: $scope,
        showClose: false
    })
    .then(function(value){
        return false;
    }, function(value){
        var promise = LeaveFactory.edit($scope.modal);
        promise.then(function(data){

            UINotification.success({
                                    message: 'You have successfully applied changes.', 
                                    title: 'SUCCESS', 
                                    delay : 5000,
                                    positionY: 'top', positionX: 'right'
                                });
            $scope.leave_types.data[k].name =  $scope.modal.name;
            $scope.leave_types.data[k].days =  $scope.modal.days;
            $scope.leave_types.data[k].code =  $scope.modal.code;
        })

        .then(null, function(data){
            
            UINotification.error({
                                    message: 'An error occured, unable to save changes, please try again.', 
                                    title: 'ERROR', 
                                    delay : 5000,
                                    positionY: 'top', positionX: 'right'
                                });
        });         

                        
    });
}
    $scope.delete_leavetype = function(k){
       
       $scope.modal = {
                title : '',
                message: 'Are you sure you want to delete this leave type?',
                save : 'Delete',
                close : 'Cancel'
            };
       ngDialog.openConfirm({
            template: 'ConfirmModal',
            className: 'ngdialog-theme-plain',
            
            scope: $scope,
            showClose: false
        })
        
        .then(function(value){
            return false;
        }, function(value){
            
            var promise = LeaveFactory.delete($scope.leave_types.data[k]);
            promise.then(function(data){
                
                $scope.archived=true;

                UINotification.success({
                                        message: 'You have successfully deleted leave type', 
                                        title: 'SUCCESS', 
                                        delay : 5000,
                                        positionY: 'top', positionX: 'right'
                                    });
                leavetypes();
            })
            .then(null, function(data){
                
                UINotification.error({
                                        message: 'An error occured, unable to delete, please try again.', 
                                        title: 'ERROR', 
                                        delay : 5000,
                                        positionY: 'top', positionX: 'right'
                                    });
            });         

                            
        });
    }

    
    $scope.add_leavetype = function(k){

    $scope.modal = {

        title : 'Add New Leave Type',
        save : 'Save',
        close : 'Cancel'

    };

    ngDialog.openConfirm({
        template: 'LeaveTypeNewModal',
        className: 'ngdialog-theme-plain custom-widththreefifty',
        preCloseCallback: function(value) {
            var nestedConfirmDialog;

            
                nestedConfirmDialog = ngDialog.openConfirm({
                    template:
                            '<p></p>' +
                            '<p>Are you sure you want add this leave type?</p>' +
                            '<div class="ngdialog-buttons">' +
                                '<button type="button" class="ngdialog-button ngdialog-button-secondary" data-ng-click="closeThisDialog(0)">No' +
                                '<button type="button" class="ngdialog-button ngdialog-button-primary" data-ng-click="confirm(1)">Yes' +
                            '</button></div>',
                    plain: true,
                    className: 'ngdialog-theme-plain custom-widththreefifty'
                });

            return nestedConfirmDialog;
        },
        scope: $scope,
        showClose: false
    })
    .then(function(value){
        return false;
    }, function(value){
        var promise = LeaveFactory.add_leavetype($scope.modal);
        promise.then(function(data){

            UINotification.success({
                                    message: 'You have successfully added leave type.', 
                                    title: 'SUCCESS', 
                                    delay : 5000,
                                    positionY: 'top', positionX: 'right'
                                });
            leavetypes();
            
        })
        .then(null, function(data){
            
            UINotification.error({
                                    message: 'An error occured, unable to save changes, please try again.', 
                                    title: 'ERROR', 
                                    delay : 5000,
                                    positionY: 'top', positionX: 'right'
                                });
        });         

                        
    });
    }


     function fetch_myemployees(){
        var filter={
            pk:$scope.profile.pk
        }

        

        var promise = LeaveFactory.get_myemployees(filter);
            promise.then(function(data){

                var a = data.data.result;
                $scope.myemployees=[];
                for(var i in a){
                    $scope.myemployees.push({
                                                pk: a[i].pk,
                                                name: a[i].myemployees,
                                                ticked: false
                                            });
                }
                

            })

        .then(null, function(data){
            
        });
    }
       


    function DEFAULTDATES(){
        var today = new Date();

        var dd = today.getDate();
        var mm = today.getMonth()+1; //January is 0!
        var yyyy = today.getFullYear();

        if(dd<10) {
            dd='0'+dd
        } 

        if(mm<10) {
            mm='0'+mm
        } 

        today = yyyy+'-'+mm+'-'+dd;

        $scope.filter.datefrom = new Date(yyyy+'-'+mm+'-01'); 
        $scope.filter.dateto = new Date();

    }

    function getMonday(d) {
        var d = new Date(d);
        var day = d.getDay(),
            diff = d.getDate() - day + (day == 0 ? -6:1); // adjust when day is sunday

        var new_date = new Date(d.setDate(diff));
        var dd = new_date.getDate();
        var mm = new_date.getMonth()+1; //January is 0!
        var yyyy = new_date.getFullYear();

        if(dd<10) {
            dd='0'+dd
        } 

        if(mm<10) {
            mm='0'+mm
        } 

        var monday = yyyy+'-'+mm+'-'+dd;

        return monday;
    }



    $scope.show_myemployees = function(){
        list();        
    }

    function list(){
        var filter ={};

        $scope.leaves_filed.status = false;
         $scope.leaves_filed.data={};
       


            var datefrom = new Date($scope.filter.datefrom);
            var dd = datefrom.getDate();
            var mm = datefrom.getMonth()+1; //January is 0!
            var yyyy = datefrom.getFullYear();

            $scope.filter.newdatefrom=yyyy+'-'+mm+'-'+dd;

             

            var dateto = new Date($scope.filter.dateto);
            var dd = dateto.getDate();
            var mm = dateto.getMonth()+1; //January is 0!
            var yyyy = dateto.getFullYear();

            $scope.filter.newdateto=yyyy+'-'+mm+'-'+dd;

      

       if($scope.filter.myemployees!== 'undefined'){
        filter.employees_pk= $scope.filter.myemployees[0].pk
        filter.datefrom=$scope.filter.newdatefrom
        filter.dateto= $scope.filter.newdateto
       }

       

       var promise = LeaveFactory.myemployees(filter);
       

       promise.then(function(data){

            $scope.leaves_filed.data=data.data.result;
            $scope.leaves_filed.status=true;


       })
        .then(null,function(data){
            $scope.leaves_filed.status=false;
        });


        
    }


   
   

    // $scope.show_myemployees = function(){

    //    myemployees();
    // }

    // function myemployees(){
        
    //     $scope.filter.archived = 'false';

    //          $scope.myemployees.status = false;
    //           $scope.myemployees.data = {};

    //     var promise = LeaveFactory.myemployees($scope.filter);
    //     promise.then(function(data){
    //         $scope.myemployees.status = true;
    //         $scope.myemployees.data = data.data.result;
            
    //     })
    //     .then(null, function(data){
    //         $scope.myemployees.status = false;
    //     });

    // }



});
app.controller('Cutoff', function(
                                        $scope,
                                        SessionFactory,
                                        EmployeesFactory,
                                        CutoffFactory,
                                        ngDialog,
                                        UINotification,
                                        md5
                                    ){

	$scope.pk='';
  $scope.level_title={};

  $scope.filter= {};
  $scope.filter.status= 1;

  $scope.type = '';

  $scope.modal = {};
  $scope.levels = {};

	$scope.filter.start_m = new Date();
	$scope.filter.start_bf = new Date();
	$scope.filter.start_bs = new Date();

	$scope.filter.end_m = new Date();
	$scope.filter.end_bf = new Date();
	$scope.filter.end_bs = new Date();

  $scope.cutofftypes = {};

    init();

    function init(){
        var promise = SessionFactory.getsession();
        promise.then(function(data){
            var _id = md5.createHash('pk');
            $scope.pk = data.data[_id];

            type();
            cutofftypes();
            
        });
    }
   

    function cutofftypes(){

        $scope.cutofftypes.status = false;
        $scope.cutofftypes.data= '';
        
        var promise = CutoffFactory.cutofftypes();
        promise.then(function(data){
            $scope.cutofftypes.status = true;
            $scope.cutofftypes.data = data.data.result;

        })
        .then(null, function(data){
            $scope.cutofftypes.status = false;
        });
    }

    $scope.show_type = function(k){
    	type();
      cutofftypes();
    }

   	function type(){
   		
   		if ($scope.filter.status == 1)
   		{
   			$scope.displayM = true;
   			$scope.displayB = false;
   			
   		}
   		else
   		{
   			$scope.displayB = true;
   			$scope.displayM = false;
   			
   		}

   		
}

	$scope.save = function(){
		type();
    var startm = new Date($scope.filter.start_m);
    var endm = new Date($scope.filter.end_m);
      var ddsm = startm.getDate();
      var ddem = endm.getDate();
      
      var startbf = new Date($scope.filter.start_bf);
    var endbf = new Date($scope.filter.end_bf);
      var dsbf = startbf.getDate();
      var debf = endbf.getDate();

    var startbs = new Date($scope.filter.start_bs);
    var endbs = new Date($scope.filter.end_bs);
      var dsbs = startbs.getDate();
      var debs = endbs.getDate();
    $scope.modal = {
                title : '',
                message: 'Are you sure you want to save cutoff dates?',
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

   		// $scope.filter["start_m"] = ddsm;
   		// $scope.filter["end_m"] = ddem;
   		// $scope.filter["start_bf"] = dsbf;
   		// $scope.filter["end_bf"] = debf;
   		// $scope.filter["start_bs"] = dsbs;
   		// $scope.filter["end_bs"] = debs;
     //    $scope.filter["pk"] = $scope.filter.status;

        var new_cutoff={};
        if($scope.filter.status == 1){
            new_cutoff = {
                'from' : ddsm,
                'to' : ddem
            };
        }
        else {
            new_cutoff = {
                'first' : {
                    'from' : dsbf,
                    'to' : debf
                },
                'second' : {
                    'from' : dsbs,
                    'to' : debs
                }
            };
        }

        $scope.filter.new_cutoff = JSON.stringify(new_cutoff);

   		var promise = CutoffFactory.submit_type($scope.filter);
   		promise.then(function(data){

        UINotification.success({
                                        message: 'You have successfully saved cutoff dates.', 
                                        title: 'SUCCESS', 
                                        delay : 5000,
                                        positionY: 'top', positionX: 'right'
                                    });  

        })
        .then(null, function(data){

          UINotification.error({
                                        message: 'An error occured, unable to save cutoff dates, please try again.', 
                                        title: 'ERROR', 
                                        delay : 5000,
                                        positionY: 'top', positionX: 'right'
                                    });
        });
	});
}

});

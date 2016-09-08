<?php

class UsersController extends AppController {

    public $components = array('Paginator', 'RequestHandler');
    public $helpers = array('Html', 'Form');
    var $uses = array('UserQuickLink', 'Vehicle', 'Customer', 'Vendor', 'Actionitem', 'ActionitemSubtask', 'Training', 'Province', 'Branch', 'Country', 'User', 'Driver', 'Department', 'UserType', 'Status', 'CompanyDetail', 'Company', 'Alert', 'AlertLog', 'AlertCategory', 'AlertType', 'AlertModule', 'UsertypeJob', 'JobFunction','JobFunctionTraining','TmpTraining');

    //function to reset the filters on page
    public function reset() {
        $this->Session->delete('filterData');
        return $this->redirect(array('action' => 'index'));
    }

    //end


    public function filterSession() {

        if (!empty($this->data)) {
            $this->Session->write('Search.users.filterData', $this->data['User']);
            $this->Session->write('filterData', $this->data['User']);
            return $this->redirect(array('action' => 'index'));
        }
        return $this->redirect(array('action' => 'index'));
    }

    //function map demo
    public function mapdemo() {
        $this->layout = 'mapLayout';
        $allLinks = $this->UserQuickLink->find('all', array(
            'conditions' => array(
                'UserQuickLink.user_id' => $this->Session->read('user_details.id'),
                'UserQuickLink.user_type' => $this->Session->read('user_type')
            ), 'order' => array('id DESC')
                )
        );
        $this->set('allLinks', $allLinks);
    }

    public function index() {

        $recordsLimit = 10;

        //Get the User's access Branch list
        $accessBranchList = $this->_getAccessBranchListArray();
        $accessDeptList = $this->_getAccessDeptListArray();
        $conditions = array();

        //$conditions = array();
        if ($accessBranchList != 'all') {
            $branchCndnt = array('User.branch_id' => $accessBranchList);
            $conditions = array_merge($conditions, $branchCndnt);
        }

        //$conditions = array();
        if ($accessDeptList != 'all') {
            $deptsCndnt = array('User.department_id' => $accessDeptList);
            $conditions = array_merge($conditions, $deptsCndnt);
        }


        //pr($conditions);
        //if($this->Session->read('Search.users.filterData')!=''){
        if ($this->Session->read('filterData') != '') {

            //checking for changing the records limit on page
            if ($this->Session->read('filterData.record_limit') != 'All') {

                $recordsLimit = $this->Session->read('filterData.record_limit');
            } else {
                $recordsLimit = 100000000;
            }
            //end
            //$this->Session->write('filterData',$this->Session->read('Search.vendors.filterData'));

            if ($this->Session->read('filterData.branch_list') != '') {
                $brachCndnt = array('User.branch_id' => $this->Session->read('filterData.branch_list'));
                $conditions = array_merge($conditions, $brachCndnt);
            }

            if ($this->Session->read('filterData.status_list') != '') {
                $statusCndnt = array('User.user_status' => $this->Session->read('filterData.status_list'));
                $conditions = array_merge($conditions, $statusCndnt);
            }

            /* 				if($this->Session->read('filterData.searchkeyword')!='Search Employee'){
              $keywordCndnt 	= array( 'OR'=>array(
              'User.user_fname like'=>'%'.$this->Session->read('filterData.searchkeyword').'%',
              'User.user_lname like'=>'%'.$this->Session->read('filterData.searchkeyword').'%',
              'User.user_emp_number like'=>'%'.$this->Session->read('filterData.searchkeyword').'%',
              'User.user_email like'=>'%'.$this->Session->read('filterData.searchkeyword').'%'
              )
              );
              $conditions		=	array_merge ( $conditions , $keywordCndnt );
              } */

            $this->User->bindModel(array('belongsTo' => array(
                    'Branch' => array(
                        'foreignKey' => false,
                        'conditions' => array('User.branch_id = Branch.id')),
                    'Department' => array(
                        'foreignKey' => false,
                        'conditions' => array('User.department_id = Department.id')
                    ),
                    'UserType' => array(
                        'foreignKey' => false,
                        'conditions' => array('User.user_level = UserType.id')
                    )
                ),
                    ), false);
            if (trim($this->Session->read('filterData.searchkeyword')) != 'Search Employee' && trim($this->Session->read('filterData.searchkeyword')) != '') {
                $searchKeywordArr = explode(' ', trim($this->Session->read('filterData.searchkeyword')));
                $OriginalSearchArr = array($this->Session->read('filterData.searchkeyword'));
                $searchKeywordArr = array_merge($searchKeywordArr, $OriginalSearchArr);
                $completeSearchArr = $this->__getSearchCritiera($searchKeywordArr, array('User', 'Branch', 'Department', 'UserType'));
                $keywordCndnt = array('OR ' => $completeSearchArr);
                $conditions = array_merge($conditions, $keywordCndnt);
            }
        }

        $this->Paginator->settings = array(
            'conditions' => $conditions,
            'limit' => $recordsLimit,
            'order' => array(
                'User.id' => 'desc'
            )
        );
        $userdetails = $this->Paginator->paginate('User');


        foreach ($userdetails as $detailKey => $data) {
            $userdetails[$detailKey]['User']['user_name'] = $this->_getEmpName($data['User']['id']);
            $userdetails[$detailKey]['User']['branch_name'] = $this->_getBranchName($data['User']['branch_id']);
            $userdetails[$detailKey]['User']['department_name'] = $this->_getDepartmentName($data['User']['department_id']);
            $activeAlerts = $this->Alert->find('count', array('conditions' => array('Alert.sent_users_id like' => '%-' . $data['User']['id'] . '-%', 'Alert.viewed_users <>' => '%-' . $data['User']['id'] . '-%')));
            //pr($activeAlerts);
            $userdetails[$detailKey]['User']['active_alerts'] = $activeAlerts;
            //pr($activeAlerts);
        }




        $status = $this->_getStatusList();
        $branchList = $this->_getBranchesList();

        $this->set('limit', $recordsLimit);
        $this->set('branchList', $branchList);
        $this->set('status', $status);
        $this->set('userdetails', $userdetails);
        $this->set('filterData', $this->Session->read('filterData'));
    }

    /**
     * Displays a add user form
     *
     * @param mixed What page to display
     * @return void
     * @throws NotFoundException When the view file could not be found
     * 	or MissingViewException in debug mode.
     */
    public function adduser() {
	
        if ($this->request->is(array('post', 'put'))) {			
            $userData = $this->data;
            $this->User->set($userData);

            if ($this->data['User']['user_login_permission'] == 'yes') {
                $this->User->validate = $this->User->validatewithPermissionsCheckyes;
            } else {
                $this->User->validate = $this->User->validatewithPermissionsCheckno;
            }
		

            if ($this->User->validates()) {
				//echo "<pre>";print_r($this->data);die;
                if (!empty($userData['User']['user_login']) && !empty($userData['User']['user_email'])) {

                    //$newpass = substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&' ) , 0 , 10 );
                    $newpass = $this->generateStrongPassword(10, 1, 1, 1);
                    App::uses('CakeEmail', 'Network/Email');

                    $userData['User']['user_password'] = md5($newpass);
                } else {
                    $userData['User']['user_password'] = '';
                }

                if (!empty($userData['User']['userjobfuntion_id'])) {
                    foreach ($this->data['User']['userjobfuntion_id'] as $userJobFuntion) {
                        $user_jobfuntion_data[] = '-' . $userJobFuntion . '-';
                    }
                    $userData['User']['userjobfuntion_id'] = implode(',', $user_jobfuntion_data);
                } else {

                    $userData['User']['userjobfuntion_id'] = '-' . $userData['User']['userjobfuntion_id'] . '-';
                }
                if (!empty($userData['User']['user_branches'])) {
                    $userData['User']['user_branches'] = implode(',', $userData['User']['user_branches']);
                    foreach ($this->data['User']['user_branches'] as $valdirect) {
                        $user_branches_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_branches_comparision'] = implode(',', $user_branches_comparision);
                } else {
                    $userData['User']['user_branches'] = $userData['User']['branch_id'];
                    $userData['User']['user_branches_comparision'] = '-' . $userData['User']['branch_id'] . '-';
                }
                if (!empty($userData['User']['user_departments'])) {
                    $userData['User']['user_departments'] = implode(',', $userData['User']['user_departments']);
                    foreach ($this->data['User']['user_departments'] as $valdirect) {
                        $user_departments_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_departments_comparision'] = implode(',', $user_departments_comparision);
                } elseif (!empty($this->data['User']['user_branches'])) {
                    $departmentID = $this->Department->find('list', array('fields' => array('id'), 'conditions' => array('Department.branch_id' => $this->data['User']['user_branches'])));
                    $userData['User']['user_departments'] = implode(',', $departmentID);
                    foreach ($departmentID as $valdirect) {
                        $user_departments_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_departments_comparision'] = implode(',', $user_departments_comparision);
                } else {
                    $userData['User']['user_departments'] = $userData['User']['department_id'];
                    $userData['User']['user_departments_comparision'] = '-' . $userData['User']['department_id'] . '-';
                }
                //pr($userData['User']['user_departments_comparision']);die;

                if (empty($userData['User']['user_level'])) {
                    $userData['User']['user_level'] = 10;
                } else {
                    $userData['User']['user_level'] = $this->data['User']['user_level'];
                }
                //pr($userData);die;

                $this->User->create();
                if ($this->User->save($userData, false)) {
                    $userId = $this->User->getLastInsertID();
//pr($userData['User']['userjobfuntion_id']);die();
					/*
					
					*/
					$get_tmp_training_data	=	$this->TmpTraining->find('all',array('conditions'=>array('TmpTraining.user_id'=>$this->Session->read('user_details.id'))));
					
					if (!empty($get_tmp_training_data))  {
						foreach ($get_tmp_training_data as $training_info)  {
							
							$training_obj	=	array();
							$training_obj['Training']['employee_id']			=	$userId;
							$training_obj['Training']['branch_id']				=	$this->data['User']['branch_id'];
							$training_obj['Training']['department_id']		=	$this->data['User']['department_id'];
							$training_obj['Training']['employee_no']			=	$this->data['User']['employee_id'];
							$training_obj['Training']['type_id']					=	$training_info['TmpTraining']['training_type_id'];
							$training_obj['Training']['date_started']			=	!empty($training_info['TmpTraining']['date_started']) ? $training_info['TmpTraining']['date_started'] : date('Y-m-d');
							$training_obj['Training']['job_function_id']		=	$training_info['TmpTraining']['job_function_id'];
							$training_obj['Training']['date_completed']		=	$training_info['TmpTraining']['date_completed'];
							$training_obj['Training']['training_completed']	=	$training_info['TmpTraining']['completed'];
							$training_obj['Training']['date_expired']			=	$training_info['TmpTraining']['date_expired'];
							$training_obj['Training']['date_entered']			=  date('Y-m-d');
							$training_obj['Training']['related_to']				=  'safety';
							$this->Training->create();
							$this->Training->save($training_obj);
						}
						$condition_for_delete5	=	array('TmpTraining.user_id'=>$this->Session->read('user_details.id')); 
						$this->TmpTraining->deleteAll($condition_for_delete5);
					}
                    //count jobfunction and update employeecount in jobfunction
                    if (!empty($userData['User']['userjobfuntion_id'])) {
                        $this->updateJobFunction($this->data['User']['userjobfuntion_id']);
                    }
                    $this->AddNewRecord($userId, $userData);

                    if ($userData['User']['user_status'] == 2) {
                        $this->NewAlertStatusInactive($userId, $userData);
                    }

                    $countActiveUsers = $this->User->find('count', array('conditions' => array('User.user_status' => 1, 'User.user_login_permission' => 'yes')));
                    $companyData = $this->CompanyDetail->findBySubdomainName(Configure::read('submodulename'));

                    $updateUserCount['CompanyDetail']['current_users'] = $countActiveUsers;

                    $this->CompanyDetail->id = $companyData['CompanyDetail']['id'];
                    $this->CompanyDetail->Save($updateUserCount);

                    $companyData1 = $this->CompanyDetail->findBySubdomainName(Configure::read('submodulename'));

                    $updateCompData['Company']['current_users'] = $countActiveUsers;
                    $this->Company->id = $companyData['CompanyDetail']['id'];
                    $this->Company->Save($updateCompData, false);

                    //Check if login name is entered then send user a new password
                    if (!empty($userData['User']['user_login']) && !empty($userData['User']['user_email'])) {

                        $userEmailData = $userData;
                        $userEmailData['User']['domain_name'] = $this->Session->read('domain_name');

                        $companyDetails = $this->Company->find('first');
                        $userEmailData['User']['comp_cont_fname'] = $companyDetails['Company']['first_name'];
                        $userEmailData['User']['comp_cont_lname'] = $companyDetails['Company']['last_name'];
                        $userEmailData['User']['comp_cont_email'] = $companyDetails['Company']['email'];
                        $userEmailData['User']['subdomain_name'] = $companyDetails['Company']['subdomain_name'];
                        App::uses('CakeEmail', 'Network/Email');

                        $Email = new CakeEmail();
                        $Email->template('default');
                        $Email->emailFormat('both');
                        $Email->from(array('admin@mysoarsolutions.com' => 'MySoarSolutions'));
                        $Email->to($userData['User']['user_email']);
                        $Email->subject('Welcome Email');
                        $Email->viewVars(compact('userEmailData', 'newpass'));
                        $Email->send();
                    }

                    $this->Session->setFlash(__("New Employee added"));
                    return $this->redirect(array('controller' => 'users', 'action' => 'view_users', $userId));
                }
            } else {
                // invalid
                $errors = $this->User->validationErrors;
            }
        }

        $branchList = $this->_getBranchesList();
        //$branchList		=	$this->Branch->find('list',array('fields'=>array('branch_name'),'conditions'=>array('Branch.date_created_status'=>'active')));

        $departmentList = array();
        if (isset($this->data['User']['branch_id']) && !empty($this->data['User']['branch_id'])) {
            $departmentList = $this->_getDepartmentList($this->data['User']['branch_id']);
        }

        $multipledepartmentList = array();
        if (isset($this->data['User']['user_branches']) && !empty($this->data['User']['user_branches'])) {
            $multipledepartmentList = $this->_getDepartmentList(implode(',', $this->data['User']['user_branches']));
        }


        //$countryList		=	$this->_getCountryList();
        //$superVisorList	=	$this->User->find('list',array('conditions'=>array('user_is_supervisor'=>'yes'),'fields'=>array('user_fname')));

        $superVisorList = $this->User->find('list', array('conditions' => array('user_is_supervisor' => 'yes')));

        //$empID = $this->User->find('list');

        $empList = $this->_getEmployeeList(implode(',', $superVisorList));

        foreach ($empList as $val) {

            $empList[$val] = $this->User->find('list', array('fields' => array('user_lname', 'user_fname', 'employee_id'), 'conditions' => array('User.id' => $val)));
        }

        //pr($empList);die;				
        $userJobList = $this->JobFunction->find('list', array('fields' => array('job_function'), 'order' => array('JobFunction.job_function' => 'asc')));

        $userLevels = $this->_getUserTypeList();
        $status = $this->_getStatusList();


        $this->set('departmentList', $departmentList);
        $this->set('branchList', $branchList);
        $this->set('userJobList', $userJobList);
        //$this->set('countrylist',$countryList);
        //$this->set('superVisorList',$superVisorList);
        $this->set('userLevels', $userLevels);
        $this->set('status', $status);
        $this->set('multipledepartmentList', $multipledepartmentList);
        $this->set('empList', $empList);
    }

    public function edituser($userId = NULL) {

        if (!$userId) {
            throw new NotFoundException(___('Invalid post'));
        }

        $post = $this->User->findById($userId);
        $oldUserData = $post;
        if (!$post) {
            throw new NotFoundException(__('invalid post'));
        }

        if ($this->request->is(array('post', 'put'))) {
            //pr($this->data);die;
            $userData = $this->data;

            $this->User->set($userData);


            if ($this->data['User']['user_login_permission'] == 'yes') {
                $this->User->validate = $this->User->validatewithPermissionsCheckyes;
            } else {
                $this->User->validate = $this->User->validatewithPermissionsCheckno;
            }


            if ($this->User->validates()) {

                if ($userData['User']['user_login'] != $userData['User']['user_old_login'] && !empty($userData['User']['user_email']) || ($post['User']['user_login_permission'] == 'no' && $this->data['User']['user_login_permission'] == 'yes')) {

                    $newpass = $this->generateStrongPassword(10, 1, 1, 1);
                    App::uses('CakeEmail', 'Network/Email');

                    $userData['User']['user_password'] = md5($newpass);
                } else {
                    $userData['User']['user_password'] = $userData['User']['user_old_password'];
                }
                // valid

                if (!empty($userData['User']['userjobfuntion_id'])) {
                    foreach ($this->data['User']['userjobfuntion_id'] as $userJobFuntion) {
                        $user_jobfuntion_data[] = '-' . $userJobFuntion . '-';
                    }
                    $userData['User']['userjobfuntion_id'] = implode(',', $user_jobfuntion_data);
                } else {

                    $userData['User']['userjobfuntion_id'] = '-' . $userData['User']['userjobfuntion_id'] . '-';
                }
                if (!empty($userData['User']['user_branches'])) {
                    $userData['User']['user_branches'] = implode(',', $userData['User']['user_branches']);
                    foreach ($this->data['User']['user_branches'] as $valdirect) {
                        $user_branches_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_branches_comparision'] = implode(',', $user_branches_comparision);
                } else {
                    $userData['User']['user_branches'] = $userData['User']['branch_id'];
                    $userData['User']['user_branches_comparision'] = '-' . $userData['User']['branch_id'] . '-';
                }
                if (!empty($userData['User']['user_departments'])) {
                    $userData['User']['user_departments'] = implode(',', $userData['User']['user_departments']);
                    foreach ($this->data['User']['user_departments'] as $valdirect) {
                        $user_departments_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_departments_comparision'] = implode(',', $user_departments_comparision);
                } elseif (!empty($this->data['User']['user_branches'])) {
                    $departmentID = $this->Department->find('list', array('fields' => array('id'), 'conditions' => array('Department.branch_id' => $this->data['User']['user_branches'])));
                    $userData['User']['user_departments'] = implode(',', $departmentID);
                    foreach ($departmentID as $valdirect) {
                        $user_departments_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_departments_comparision'] = implode(',', $user_departments_comparision);
                } else {
                    $userData['User']['user_departments'] = $userData['User']['department_id'];
                    $userData['User']['user_departments_comparision'] = '-' . $userData['User']['department_id'] . '-';
                }

                if (empty($userData['User']['user_level'])) {
                    $userData['User']['user_level'] = 10;
                } else {
                    $userData['User']['user_level'] = $this->data['User']['user_level'];
                }

                $this->User->id = $userId;

                $userOldData = $this->User->find('first', array('fields' => 'user_status', 'conditions' => array('User.id' => $userId)));

                //change existing driver status according to user status starts here

                $existingDriver = $this->Driver->find('first', array('conditions' => array('Driver.emp_id' => $userId)));
                //pr($existingDriver);die;
                if (count($existingDriver)) {
                    $existingDriver['Driver']['status'] = $userData['User']['user_status'];
                    $this->Driver->id = $existingDriver['Driver']['id'];
                    $this->Driver->save($existingDriver);
                }
                //change existing driver status according to user status ends here	

                if ($this->User->save($userData, false)) {
					
					
					$get_tmp_training_data	=	$this->TmpTraining->find('all',array('conditions'=>array('TmpTraining.user_id'=>$this->Session->read('user_details.id'))));
					//echo "<pre>"; print_r ($get_tmp_training_data);die;
					if (!empty($get_tmp_training_data))  {
						foreach ($get_tmp_training_data as $training_info)  {
							if ($training_info['TmpTraining']['training_id'] != 0)  {
								
								if (!empty($training_info['TmpTraining']['date_completed']))  {
									$date_completed = $training_info['TmpTraining']['date_completed'];
								}  else  {
									$date_completed = Null;
								}
								
								if (!empty($training_info['TmpTraining']['date_expired']))  {
									$date_expired = $training_info['TmpTraining']['date_expired'];
								}  else  {
									$date_expired = Null;
								}
								
								$get_training_info = $this->Training->find('first',array('conditions'=>array('Training.id'=>$training_info['TmpTraining']['training_id'])));
								
								if (isset($get_training_info['Training']['employee_id']))  {
									$this->Training->id = $training_info['TmpTraining']['training_id'];
									$training_obj1	=	array();
									$training_obj1['Training']['date_completed']		=	$date_completed;
									$training_obj1['Training']['training_completed']	=	$training_info['TmpTraining']['completed'];
									$training_obj1['Training']['date_expired']			=	$date_expired;
									//echo "<pre>"; print_r ($training_obj1);
									$this->Training->save($training_obj1);
								}								
			
							}  else  {
								
								if ($training_info['TmpTraining']['record_created_by'] == 'expire')  {									
							
									$this->Training->updateAll(
										array(
											'Training.date_expire_status'=>"'yes'"
										),
										array(
											'Training.employee_id'=>$userId,
											'Training.type_id'		=>$training_info['TmpTraining']['training_type_id']
										)
									);
								}						

								$repeat_assoc_count = $this->Training->find(
									'count',array(
										'conditions'	=> array(
											'Training.employee_id'	=> $userId,
											'Training.type_id'			=> $training_info['TmpTraining']['training_type_id'],
											'Training.is_assoc'			=> 'yes'
										),
										'contain' => array()
									)
								);
								
								//echo $training_info['TmpTraining']['training_type_id'].'---';
								//echo $repeat_assoc_count.'<br>';
								
								if ($repeat_assoc_count > 0)  {
									
									$this->Training->updateAll(
										array(
											'Training.repeat_assoc'=>"'yes'"
										),
										array(
											'Training.employee_id'	=> $userId,
											'Training.type_id'			=> $training_info['TmpTraining']['training_type_id'],
											'Training.is_assoc'			=> 'yes'
										)
									);
									$repeat_assoc = 'yes';
								}  else  {
									$repeat_assoc = 'no';
								}
								
								$training_obj	=	array();
								$training_obj['Training']['employee_id']			=	$userId;
								$training_obj['Training']['branch_id']				=	$this->data['User']['branch_id'];
								$training_obj['Training']['department_id']		=	$this->data['User']['department_id'];
								$training_obj['Training']['employee_no']			=	$this->data['User']['employee_id'];
								$training_obj['Training']['type_id']						=	$training_info['TmpTraining']['training_type_id'];
								$training_obj['Training']['date_started']			=	!empty($training_info['TmpTraining']['date_started']) ? $training_info['TmpTraining']['date_started'] : date('Y-m-d');
								$training_obj['Training']['job_function_id']		=	$training_info['TmpTraining']['job_function_id'];
								$training_obj['Training']['record_created_by']	=	$training_info['TmpTraining']['record_created_by'];
								$training_obj['Training']['date_completed']		=	!empty($training_info['TmpTraining']['date_completed']) ? $training_info['TmpTraining']['date_completed'] : Null;
								$training_obj['Training']['training_completed']	=	$training_info['TmpTraining']['completed'];
								$training_obj['Training']['date_expired']			=	!empty($training_info['TmpTraining']['date_expired']) ? $training_info['TmpTraining']['date_expired'] : Null;
								$training_obj['Training']['date_entered']			=  date('Y-m-d');
								$training_obj['Training']['related_to']				=  'safety';
								//$training_obj['Training']['repeat_assoc']			=  $repeat_assoc;
								//echo "<pre>";print_r ($training_obj);
								$this->Training->create();
								$this->Training->save($training_obj);
							}							
						}
						//die;
						$condition_for_delete4	=	array('TmpTraining.user_id'=>$this->Session->read('user_details.id')); 
						$this->TmpTraining->deleteAll($condition_for_delete4);
						//die;
					}
					

                    if (!empty($oldUserData['User']['userjobfuntion_id'])) {
                        $this->reduceEmployeeCount($oldUserData);
                    }

                    if (!empty($this->data['User']['userjobfuntion_id'])) {
                        $this->updateJobFunction($this->data['User']['userjobfuntion_id']);
                    }

                    if ($userOldData['User']['user_status'] == $userData['User']['user_status']) {
                        
                    } else {
                        if ($userData['User']['user_status'] == 2) {
                            $this->NewAlertStatusInactive($userId, $userData);
                        }
                    }

                    $countActiveUsers = $this->User->find('count', array('conditions' => array('User.user_status' => 1, 'User.user_login_permission' => 'yes')));
                    $companyData = $this->CompanyDetail->findBySubdomainName(Configure::read('submodulename'));

                    $updateUserCount['CompanyDetail']['current_users'] = $countActiveUsers;
                    $this->CompanyDetail->id = $companyData['CompanyDetail']['id'];
                    $this->CompanyDetail->Save($updateUserCount);

                    $updateCompData['Company']['current_users'] = $countActiveUsers;
                    $this->Company->id = $companyData['CompanyDetail']['id'];
                    $this->Company->Save($updateCompData, false);

                    //Check if login name is entered then send user a new password			

                    if ((($userData['User']['user_login'] != $userData['User']['user_old_login'] && !empty($userData['User']['user_email'])) || ($userData['User']['user_login'] != $userData['User']['user_old_login'] && !empty($userData['User']['user_email']))) || ($post['User']['user_login_permission'] == 'no' && $this->data['User']['user_login_permission'] == 'yes')) {

                        $userEmailData = $userData;
                        $userEmailData['User']['domain_name'] = $this->Session->read('domain_name');


                        $companyDetails = $this->Company->find('first');

                        $userEmailData['User']['comp_cont_fname'] = $companyDetails['Company']['first_name'];
                        $userEmailData['User']['comp_cont_lname'] = $companyDetails['Company']['last_name'];
                        $userEmailData['User']['comp_cont_email'] = $companyDetails['Company']['email'];
                        $userEmailData['User']['subdomain_name'] = $companyDetails['Company']['subdomain_name'];
                        App::uses('CakeEmail', 'Network/Email');


                        $Email = new CakeEmail();
                        $Email->template('default');
                        $Email->emailFormat('both');
                        $Email->from(array('admin@mysoarsolutions.com' => 'MySoarSolutions'));
                        $Email->to($userData['User']['user_email']);
                        $Email->subject('Welcome Email');
                        $Email->viewVars(compact('userEmailData', 'newpass'));
                        $Email->send();
                    }

                    $this->Session->setFlash(__("Employee has been updated"));
                    return $this->redirect(array('controller' => 'users', 'action' => 'view_users', $userId));
                }
            } else {
                // invalid
                $errors = $this->User->validationErrors;
            }
        }

        $departmentList = array();
        if (isset($this->data['User']['branch_id']) && !empty($this->data['User']['branch_id'])) {
            $departmentList = $this->_getDepartmentList($this->data['User']['branch_id']);
        }

        $multipledepartmentList = array();
        if (isset($this->data['User']['user_branches']) && !empty($this->data['User']['user_branches'])) {
            $deptArray = $this->_getDepartmentListwithbranchname(implode(',', $this->data['User']['user_branches']));

            foreach ($deptArray as $key => $deptArr) {
                $branchname = $this->_getBranchName($deptArr['Department']['branch_id']);
                $multipledepartmentList[$deptArr['Department']['id']] = $deptArr['Department']['department_name'] . '(' . $branchname . ')';
            }
        }

        $new_str = str_replace("-", '', $post['User']['userjobfuntion_id']);
        $post['User']['userjobfuntion_id'] = explode(',', $new_str);
        $post['User']['user_branches'] = explode(',', $post['User']['user_branches']);
        $post['User']['user_departments'] = explode(',', $post['User']['user_departments']);

        if (!$this->request->data) {
            $this->request->data = $post;
            $departmentList = array();
            if ($post['User']['branch_id']) {
                $departmentList = $this->_getDepartmentList($post['User']['branch_id']);
            }

            $multipledepartmentList = array();
            if ($post['User']['user_branches']) {
                $deptArray = $this->_getDepartmentListwithbranchname(implode(',', $post['User']['user_branches']));

                foreach ($deptArray as $key => $deptArr) {
                    $branchname = $this->_getBranchName($deptArr['Department']['branch_id']);
                    $multipledepartmentList[$deptArr['Department']['id']] = $deptArr['Department']['department_name'] . '(' . $branchname . ')';
                }
            }
        }



        $userLevels = $this->_getUserTypeList();
        $status = $this->_getStatusList();
        $branchList = $this->_getBranchesList();

        $countryList = $this->Country->find('list', array('fields' => array('country_name')));


        $superVisorList = $this->User->find('list', array('conditions' => array('user_is_supervisor' => 'yes')));
        $empList = $this->_getEmployeeList(implode(',', $superVisorList));

        foreach ($empList as $val) {

            $empList[$val] = $this->User->find('list', array('fields' => array('user_lname', 'user_fname', 'employee_id'), 'conditions' => array('User.id' => $val)));
        }
        $userJobList = $this->JobFunction->find('list', array('fields' => array('job_function'), 'order' => array('JobFunction.job_function' => 'asc')));
        $userLevels = $this->_getUserTypeList();
        $status = $this->_getStatusList();

        $this->set('departmentList', $departmentList);
        $this->set('branchList', $branchList);
        $this->set('countrylist', $countryList);
        $this->set('userJobList', $userJobList);
        //$this->set('superVisorList',$superVisorList);
        $this->set('userLevels', $userLevels);
        $this->set('status', $status);
        $this->set('multipledepartmentList', $multipledepartmentList);
        $this->set('empList', $empList);
        $this->set('userId', $userId);
    }
	
	public function edituser1($userId = NULL) {

        if (!$userId) {
            throw new NotFoundException(___('Invalid post'));
        }

        $post = $this->User->findById($userId);
        $oldUserData = $post;
        if (!$post) {
            throw new NotFoundException(__('invalid post'));
        }

        if ($this->request->is(array('post', 'put'))) {
            //pr($this->data);die;
            $userData = $this->data;

            $this->User->set($userData);


            if ($this->data['User']['user_login_permission'] == 'yes') {
                $this->User->validate = $this->User->validatewithPermissionsCheckyes;
            } else {
                $this->User->validate = $this->User->validatewithPermissionsCheckno;
            }


            if ($this->User->validates()) {

                if ($userData['User']['user_login'] != $userData['User']['user_old_login'] && !empty($userData['User']['user_email']) || ($post['User']['user_login_permission'] == 'no' && $this->data['User']['user_login_permission'] == 'yes')) {

                    $newpass = $this->generateStrongPassword(10, 1, 1, 1);
                    App::uses('CakeEmail', 'Network/Email');

                    $userData['User']['user_password'] = md5($newpass);
                } else {
                    $userData['User']['user_password'] = $userData['User']['user_old_password'];
                }
                // valid

                if (!empty($userData['User']['userjobfuntion_id'])) {
                    foreach ($this->data['User']['userjobfuntion_id'] as $userJobFuntion) {
                        $user_jobfuntion_data[] = '-' . $userJobFuntion . '-';
                    }
                    $userData['User']['userjobfuntion_id'] = implode(',', $user_jobfuntion_data);
                } else {

                    $userData['User']['userjobfuntion_id'] = '-' . $userData['User']['userjobfuntion_id'] . '-';
                }
                if (!empty($userData['User']['user_branches'])) {
                    $userData['User']['user_branches'] = implode(',', $userData['User']['user_branches']);
                    foreach ($this->data['User']['user_branches'] as $valdirect) {
                        $user_branches_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_branches_comparision'] = implode(',', $user_branches_comparision);
                } else {
                    $userData['User']['user_branches'] = $userData['User']['branch_id'];
                    $userData['User']['user_branches_comparision'] = '-' . $userData['User']['branch_id'] . '-';
                }
                if (!empty($userData['User']['user_departments'])) {
                    $userData['User']['user_departments'] = implode(',', $userData['User']['user_departments']);
                    foreach ($this->data['User']['user_departments'] as $valdirect) {
                        $user_departments_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_departments_comparision'] = implode(',', $user_departments_comparision);
                } elseif (!empty($this->data['User']['user_branches'])) {
                    $departmentID = $this->Department->find('list', array('fields' => array('id'), 'conditions' => array('Department.branch_id' => $this->data['User']['user_branches'])));
                    $userData['User']['user_departments'] = implode(',', $departmentID);
                    foreach ($departmentID as $valdirect) {
                        $user_departments_comparision[] = '-' . $valdirect . '-';
                    }
                    $userData['User']['user_departments_comparision'] = implode(',', $user_departments_comparision);
                } else {
                    $userData['User']['user_departments'] = $userData['User']['department_id'];
                    $userData['User']['user_departments_comparision'] = '-' . $userData['User']['department_id'] . '-';
                }

                if (empty($userData['User']['user_level'])) {
                    $userData['User']['user_level'] = 10;
                } else {
                    $userData['User']['user_level'] = $this->data['User']['user_level'];
                }

                $this->User->id = $userId;

                $userOldData = $this->User->find('first', array('fields' => 'user_status', 'conditions' => array('User.id' => $userId)));

                //change existing driver status according to user status starts here

                $existingDriver = $this->Driver->find('first', array('conditions' => array('Driver.emp_id' => $userId)));
                //pr($existingDriver);die;
                if (count($existingDriver)) {
                    $existingDriver['Driver']['status'] = $userData['User']['user_status'];
                    $this->Driver->id = $existingDriver['Driver']['id'];
                    $this->Driver->save($existingDriver);
                }
                //change existing driver status according to user status ends here	

                if ($this->User->save($userData, false)) {
					
					
					$get_tmp_training_data	=	$this->TmpTraining->find('all',array('conditions'=>array('TmpTraining.user_id'=>$this->Session->read('user_details.id'))));
					//echo "<pre>"; print_r ($get_tmp_training_data);die;
					if (!empty($get_tmp_training_data))  {
						foreach ($get_tmp_training_data as $training_info)  {
							if ($training_info['TmpTraining']['training_id'] != 0)  {
								
								if (!empty($training_info['TmpTraining']['date_completed']))  {
									$date_completed = $training_info['TmpTraining']['date_completed'];
								}  else  {
									$date_completed = Null;
								}
								
								if (!empty($training_info['TmpTraining']['date_expired']))  {
									$date_expired = $training_info['TmpTraining']['date_expired'];
								}  else  {
									$date_expired = Null;
								}
								
								$get_training_info = $this->Training->find('first',array('conditions'=>array('Training.id'=>$training_info['TmpTraining']['training_id'])));
								
								if (isset($get_training_info['Training']['employee_id']))  {
									$this->Training->id = $training_info['TmpTraining']['training_id'];
									$training_obj1	=	array();
									$training_obj1['Training']['date_completed']		=	$date_completed;
									$training_obj1['Training']['training_completed']	=	$training_info['TmpTraining']['completed'];
									$training_obj1['Training']['date_expired']			=	$date_expired;
									//echo "<pre>"; print_r ($training_obj1);
									$this->Training->save($training_obj1);
								}								
			
							}  else  {
								
								if ($training_info['TmpTraining']['record_created_by'] == 'expire')  {
									$this->Training->updateAll(
										array(
											'Training.date_expire_status'=>"'yes'"
										),
										array(
											'Training.employee_id'=>$userId,
											'Training.type_id'		=>$training_info['TmpTraining']['training_type_id']
										)
									);
								}								
								
								$training_obj	=	array();
								$training_obj['Training']['employee_id']			=	$userId;
								$training_obj['Training']['branch_id']				=	$this->data['User']['branch_id'];
								$training_obj['Training']['department_id']		=	$this->data['User']['department_id'];
								$training_obj['Training']['employee_no']			=	$this->data['User']['employee_id'];
								$training_obj['Training']['type_id']						=	$training_info['TmpTraining']['training_type_id'];
								$training_obj['Training']['date_started']			=	!empty($training_info['TmpTraining']['date_started']) ? $training_info['TmpTraining']['date_started'] : date('Y-m-d');
								$training_obj['Training']['job_function_id']		=	$training_info['TmpTraining']['job_function_id'];
								$training_obj['Training']['record_created_by']	=	$training_info['TmpTraining']['record_created_by'];
								$training_obj['Training']['date_completed']		=	!empty($training_info['TmpTraining']['date_completed']) ? $training_info['TmpTraining']['date_completed'] : Null;
								$training_obj['Training']['training_completed']	=	$training_info['TmpTraining']['completed'];
								$training_obj['Training']['date_expired']			=	!empty($training_info['TmpTraining']['date_expired']) ? $training_info['TmpTraining']['date_expired'] : Null;
								$training_obj['Training']['date_entered']			=  date('Y-m-d');
								$training_obj['Training']['related_to']				=  'safety';
								//echo "<pre>";print_r ($training_obj);
								$this->Training->create();
								$this->Training->save($training_obj);
							}							
						}
						//die;
						$condition_for_delete4	=	array('TmpTraining.user_id'=>$this->Session->read('user_details.id')); 
						$this->TmpTraining->deleteAll($condition_for_delete4);
						//die;
					}
					

                    if (!empty($oldUserData['User']['userjobfuntion_id'])) {
                        $this->reduceEmployeeCount($oldUserData);
                    }

                    if (!empty($this->data['User']['userjobfuntion_id'])) {
                        $this->updateJobFunction($this->data['User']['userjobfuntion_id']);
                    }

                    if ($userOldData['User']['user_status'] == $userData['User']['user_status']) {
                        
                    } else {
                        if ($userData['User']['user_status'] == 2) {
                            $this->NewAlertStatusInactive($userId, $userData);
                        }
                    }

                    $countActiveUsers = $this->User->find('count', array('conditions' => array('User.user_status' => 1, 'User.user_login_permission' => 'yes')));
                    $companyData = $this->CompanyDetail->findBySubdomainName(Configure::read('submodulename'));

                    $updateUserCount['CompanyDetail']['current_users'] = $countActiveUsers;
                    $this->CompanyDetail->id = $companyData['CompanyDetail']['id'];
                    $this->CompanyDetail->Save($updateUserCount);

                    $updateCompData['Company']['current_users'] = $countActiveUsers;
                    $this->Company->id = $companyData['CompanyDetail']['id'];
                    $this->Company->Save($updateCompData, false);

                    //Check if login name is entered then send user a new password			

                    if ((($userData['User']['user_login'] != $userData['User']['user_old_login'] && !empty($userData['User']['user_email'])) || ($userData['User']['user_login'] != $userData['User']['user_old_login'] && !empty($userData['User']['user_email']))) || ($post['User']['user_login_permission'] == 'no' && $this->data['User']['user_login_permission'] == 'yes')) {

                        $userEmailData = $userData;
                        $userEmailData['User']['domain_name'] = $this->Session->read('domain_name');


                        $companyDetails = $this->Company->find('first');

                        $userEmailData['User']['comp_cont_fname'] = $companyDetails['Company']['first_name'];
                        $userEmailData['User']['comp_cont_lname'] = $companyDetails['Company']['last_name'];
                        $userEmailData['User']['comp_cont_email'] = $companyDetails['Company']['email'];
                        $userEmailData['User']['subdomain_name'] = $companyDetails['Company']['subdomain_name'];
                        App::uses('CakeEmail', 'Network/Email');


                        $Email = new CakeEmail();
                        $Email->template('default');
                        $Email->emailFormat('both');
                        $Email->from(array('admin@mysoarsolutions.com' => 'MySoarSolutions'));
                        $Email->to($userData['User']['user_email']);
                        $Email->subject('Welcome Email');
                        $Email->viewVars(compact('userEmailData', 'newpass'));
                        $Email->send();
                    }

                    $this->Session->setFlash(__("Employee has been updated"));
                    return $this->redirect(array('controller' => 'users', 'action' => 'view_users', $userId));
                }
            } else {
                // invalid
                $errors = $this->User->validationErrors;
            }
        }

        $departmentList = array();
        if (isset($this->data['User']['branch_id']) && !empty($this->data['User']['branch_id'])) {
            $departmentList = $this->_getDepartmentList($this->data['User']['branch_id']);
        }

        $multipledepartmentList = array();
        if (isset($this->data['User']['user_branches']) && !empty($this->data['User']['user_branches'])) {
            $deptArray = $this->_getDepartmentListwithbranchname(implode(',', $this->data['User']['user_branches']));

            foreach ($deptArray as $key => $deptArr) {
                $branchname = $this->_getBranchName($deptArr['Department']['branch_id']);
                $multipledepartmentList[$deptArr['Department']['id']] = $deptArr['Department']['department_name'] . '(' . $branchname . ')';
            }
        }

        $new_str = str_replace("-", '', $post['User']['userjobfuntion_id']);
        $post['User']['userjobfuntion_id'] = explode(',', $new_str);
        $post['User']['user_branches'] = explode(',', $post['User']['user_branches']);
        $post['User']['user_departments'] = explode(',', $post['User']['user_departments']);

        if (!$this->request->data) {
            $this->request->data = $post;
            $departmentList = array();
            if ($post['User']['branch_id']) {
                $departmentList = $this->_getDepartmentList($post['User']['branch_id']);
            }

            $multipledepartmentList = array();
            if ($post['User']['user_branches']) {
                $deptArray = $this->_getDepartmentListwithbranchname(implode(',', $post['User']['user_branches']));

                foreach ($deptArray as $key => $deptArr) {
                    $branchname = $this->_getBranchName($deptArr['Department']['branch_id']);
                    $multipledepartmentList[$deptArr['Department']['id']] = $deptArr['Department']['department_name'] . '(' . $branchname . ')';
                }
            }
        }



        $userLevels = $this->_getUserTypeList();
        $status = $this->_getStatusList();
        $branchList = $this->_getBranchesList();

        $countryList = $this->Country->find('list', array('fields' => array('country_name')));


        $superVisorList = $this->User->find('list', array('conditions' => array('user_is_supervisor' => 'yes')));
        $empList = $this->_getEmployeeList(implode(',', $superVisorList));

        foreach ($empList as $val) {

            $empList[$val] = $this->User->find('list', array('fields' => array('user_lname', 'user_fname', 'employee_id'), 'conditions' => array('User.id' => $val)));
        }
        $userJobList = $this->JobFunction->find('list', array('fields' => array('job_function'), 'order' => array('JobFunction.job_function' => 'asc')));
        $userLevels = $this->_getUserTypeList();
        $status = $this->_getStatusList();

        $this->set('departmentList', $departmentList);
        $this->set('branchList', $branchList);
        $this->set('countrylist', $countryList);
        $this->set('userJobList', $userJobList);
        //$this->set('superVisorList',$superVisorList);
        $this->set('userLevels', $userLevels);
        $this->set('status', $status);
        $this->set('multipledepartmentList', $multipledepartmentList);
        $this->set('empList', $empList);
        $this->set('userId', $userId);
    }
	
    /**
     * Return the Department list of selected Branch
     *
     * @param mixed What page to display
     * @return Department List
     * @throws NotFoundException When the view file could not be found
     * 	or MissingViewException in debug mode.
     */
    public function getDepartments($branch_id = NULL) {
        $this->layout = "";
        $departmentList = $this->_getDepartmentList($branch_id);
        $this->set('departmentList', $departmentList);
    }

    public function view_users($id = NULL) {
        $departments = array();
        $userDetails = $this->User->find('first', array('conditions' => array('User.id' => $id)));

        //pr($userDetails);
        //$userDetails['User']['employee_name']	=	$this->_getEmpName( $userDetails['User']['id'] );		
        $userDetails['User']['branch_name'] = $this->_getBranchName($userDetails['User']['branch_id']);
        $userDetails['User']['department_name'] = $this->_getDepartmentName($userDetails['User']['department_id']);

        $DriverEmployee = $this->Driver->find('first', array('conditions' => array('Driver.emp_id' => $id)));

        if (empty($DriverEmployee)) {
            $DriverEmployee = '';
        }

        $userDetails['User']['user_is_supervisor_fname'] = $this->_getEmpName($userDetails['User']['id']);
        $userDetails['User']['user_status_name'] = $this->_getStatusName($userDetails['User']['user_status']);
        $userDetails['User']['user_level_name'] = $this->_getUserType($userDetails['User']['user_level']);
        $userDetails['User']['direct_supervisor_name'] = $this->_getEmpName($userDetails['User']['user_supervisor']);
        $userDetails['User']['user_start_date'] = $this->_dateFormat($userDetails['User']['user_start_date']);
        $userDetails['User']['user_end_date'] = $this->_dateFormat($userDetails['User']['user_end_date']);
        $userDetails['User']['created_date'] = $this->_dateFormat($userDetails['User']['created_date']);
        $userDetails['User']['closed_date'] = $this->_dateFormat($userDetails['User']['closed_date']);

        if (!empty($userDetails['User']['userjobfuntion_id'])) {
            $new_str = str_replace("-", '', $userDetails['User']['userjobfuntion_id']);
            $userJobtypeID = explode(',', $new_str);
            $userJobList = $this->JobFunction->find('list', array('fields' => array('job_function'), 'conditions' => array('JobFunction.id' => $userJobtypeID)));

            $userDetails['User']['job_type'] = implode(', ', $userJobList);
        } else {
            $userDetails['User']['job_type'] = '';
        }
        if (!empty($userDetails['User']['user_branches'])) {
            $userBranchesID = explode(',', $userDetails['User']['user_branches']);
            $branches = $this->Branch->find('list', array('fields' => array('branch_name'), 'conditions' => array('Branch.id' => $userBranchesID)));
            $userDetails['User']['user_branches_names'] = implode(',', $branches);
        } else {
            $userDetails['User']['user_branches_names'] = '';
        }

        if (!empty($userDetails['User']['user_departments'])) {
            $userdepID = explode(',', $userDetails['User']['user_departments']);
            $departmentsArr = $this->Department->find('all', array('fields' => array('department_name', 'branch_id'), 'conditions' => array('Department.id' => $userdepID)));

            if (count($departmentsArr)) {
                foreach ($departmentsArr as $key => $deptArr) {
                    $branchname = $this->_getBranchName($deptArr['Department']['branch_id']);
                    $departments[] = $deptArr['Department']['department_name'] . '(' . $branchname . ')';
                }
                $userDetails['User']['user_departments_names'] = implode(',', $departments);
            } else {
                $userDetails['User']['user_departments_names'] = '';
            }
        } else {
            $userDetails['User']['user_departments_names'] = '';
        }
		//echo "<pre>";print_r($userDetails);die;
        $this->set('data', $userDetails);
        $this->set('id', $id);
        $this->set('DriverEmployee', $DriverEmployee);
    }

    public function viewPdf($id = NULL) {

        $userDetails = $this->User->find('first', array('conditions' => array('User.id' => $id)));

        //$userDetails['User']['employee_name']	=	$this->_getEmpName( $userDetails['User']['id'] );	
        $userDetails['User']['branch_name'] = $this->_getBranchName($userDetails['User']['branch_id']);
        $userDetails['User']['department_name'] = $this->_getDepartmentName($userDetails['User']['department_id']);

        $userData = $this->User->find('first', array('conditions' => array('User.id' => $userDetails['User']['id'])));

        $userDetails['User']['user_is_supervisor_fname'] = $this->_getEmpName($userDetails['User']['id']);
        $userDetails['User']['user_status_name'] = $this->_getStatusName($userDetails['User']['user_status']);
        $userDetails['User']['user_level_name'] = $this->_getUserType($userDetails['User']['user_level']);
        $userDetails['User']['direct_supervisor_name'] = $this->_getEmpName($userDetails['User']['user_supervisor']);

        $userDetails['User']['user_start_date'] = $this->_dateFormat($userDetails['User']['user_start_date']);
        $userDetails['User']['user_end_date'] = $this->_dateFormat($userDetails['User']['user_end_date']);
        $userDetails['User']['created_date'] = $this->_dateFormat($userDetails['User']['created_date']);
        $userDetails['User']['closed_date'] = $this->_dateFormat($userDetails['User']['closed_date']);
        if (!empty($userDetails['User']['userjobfuntion_id'])) {
            $new_str = str_replace("-", '', $userDetails['User']['userjobfuntion_id']);
            $userJobtypeID = explode(',', $new_str);
            $userJobList = $this->JobFunction->find('list', array('fields' => array('job_function'), 'conditions' => array('JobFunction.id' => $userJobtypeID)));

            $userDetails['User']['job_type'] = implode(', ', $userJobList);
        } else {
            $userDetails['User']['job_type'] = '';
        }

        if (!empty($userDetails['User']['user_branches'])) {
            $userBranchesID = explode(',', $userDetails['User']['user_branches']);
            $branches = $this->Branch->find('list', array('fields' => array('branch_name'), 'conditions' => array('Branch.id' => $userBranchesID)));
            $userDetails['User']['user_branches_names'] = implode(',', $branches);
        } else {
            $userDetails['User']['user_branches_names'] = '';
        }

        if (!empty($userDetails['User']['user_departments'])) {
            $userdepID = explode(',', $userDetails['User']['user_departments']);
            $departmentsArr = $this->Department->find('all', array('fields' => array('department_name', 'branch_id'), 'conditions' => array('Department.id' => $userdepID)));
            if (count($departmentsArr)) {
                foreach ($departmentsArr as $key => $deptArr) {
                    $branchname = $this->_getBranchName($deptArr['Department']['branch_id']);
                    $departments[] = $deptArr['Department']['department_name'] . '(' . $branchname . ')';
                }
                $userDetails['User']['user_departments_names'] = implode(',', $departments);
            } else {
                $userDetails['User']['user_departments_names'] = '';
            }
        } else {
            $userDetails['User']['user_departments_names'] = '';
        }
        $this->set('data', $userDetails);
    }

    public function viewPdfListing() {
        Configure::write('debug', 0); // Otherwise we cannot use this method while developing
        $this->layout = 'pdf'; //this will use the pdf.ctp layout		
        //Get the User's access Branch list
        $accessBranchList = $this->_getAccessBranchListArray();
        $accessDeptList = $this->_getAccessDeptListArray();
        $conditions = array();

        //$conditions = array();
        if ($accessBranchList != 'all') {
            $branchCndnt = array('User.branch_id' => $accessBranchList);
            $conditions = array_merge($conditions, $branchCndnt);
        }

        //$conditions = array();
        if ($accessDeptList != 'all') {
            $deptsCndnt = array('User.department_id' => $accessDeptList);
            $conditions = array_merge($conditions, $deptsCndnt);
        }

        //$userDetails	=	$this->User->find('all',array('conditions'=>$conditions));

        $userDetails = $this->User->find('all', array('conditions' => $conditions, 'order' => array('User.id' => 'desc')));

        foreach ($userDetails as $detailKey => $data) {
            $userDetails[$detailKey]['User']['user_name'] = $this->_getEmpName($data['User']['id']);
            $userDetails[$detailKey]['User']['branch_name'] = $this->_getBranchName($data['User']['branch_id']);
            $userDetails[$detailKey]['User']['department_name'] = $this->_getDepartmentName($data['User']['department_id']);
            $userDetails[$detailKey]['User']['status_name'] = $this->_getStatusName($data['User']['user_status']);
        }

        $this->set('data', $userDetails);
        $this->render();
    }

    public function getMultipledepartments($branch_id = Null) {

        $this->layout = "";
        $deptArray = array();

        //	

        $user_branches = $this->request->data('user_branches');
        $multipledepartmentList = $this->_getDepartmentListwithbranchname(implode(',', $user_branches));

        foreach ($multipledepartmentList as $key => $deptArr) {
            $branchname = $this->_getBranchName($deptArr['Department']['branch_id']);
            $deptArray[$deptArr['Department']['id']] = $deptArr['Department']['department_name'] . '(' . $branchname . ')';
        }

        $this->set('multipledepartmentList', $deptArray);
    }

    //function for add new record
    public function AddNewRecord($assocID = null, $data = array()) {
        //pr($data);
        $defaultDate = date('Y-m-d');
        if ($data['User']['user_status'] == 1) {

            $loginUname = $this->Session->read('user_details');
            $user_type = $this->Session->read('user_type');
            if ($user_type == 'superAdmin') {
                $loginid = $this->Session->read('user_details.id');
            } elseif ($user_type == 'companyLogin') {
                $loginid = $this->Session->read('user_details.id');
            } else {
                $loginid = $this->Session->read('user_details.id');
            }

            $driverName = count($data) ? ucfirst($data['User']['user_fname']) . ' ' . ucfirst($data['User']['user_lname']) . ' (' . $data['User']['employee_id'] . ')' : '';

            $EmployeealertMessage = '';
            $OtheralertMessage = 'New Record and login created for ' . $driverName;

            $url = 'users/view_users/' . $assocID;

            $alert_moduleid = $this->add_alert_module('Employee');

            //saving alert category
            $alert_categoryid = $this->add_alert_category('User');
            //end 	
            //saving alert Type
            $alert_categoryTypeid = $this->add_alert_type($alert_categoryid, 'new record created', 'new record created');
            //end 		
            //direct supervisor
            if (!empty($data['User']['user_supervisor'])) {
                $directSupervisorId = $data['User']['user_supervisor'];
            } else {
                $directSupervisorId = '';
            }

            if (!empty($directSupervisorId)) {
                $countsupervisor = count($directSupervisorId);
            } else {
                $countsupervisor = 0;
            }


            //Safety Officer		
            $conditions = array('User.user_branches_comparision like' => '%-' . $data['User']['branch_id'] . '-%', 'User.user_departments_comparision like' => '%-' . $data['User']['department_id'] . '-%', 'User.user_level' => 8);

            $associatedsafety = $this->User->find('all', array('fields' => array('id'), 'conditions' => $conditions));


            foreach ($associatedsafety as $associds) {
                $associds = $associds['User']['id'];
                $SafetyOfficerId[] = $associds;
            }

            if (!empty($SafetyOfficerId)) {
                $DriversafetyOfficerId = $SafetyOfficerId;
            } else {
                $DriversafetyOfficerId = '';
            }
            //pr($DriversafetyOfficerId);die;					

            if (!empty($DriversafetyOfficerId)) {
                $DriversafetyOfficerIdcount = count($DriversafetyOfficerId);
            } else {
                $DriversafetyOfficerIdcount = 0;
            }


            $totalsentUsersCount = $countsupervisor + $DriversafetyOfficerIdcount;

            if (!empty($directSupervisorId)) {
                $directSupervisorId = array($directSupervisorId);
                foreach ($directSupervisorId as $valdirect) {
                    $directSupervisorval[] = '-' . $valdirect . '-';
                }
                $directSupervisorId = implode(',', $directSupervisorval);
            } else {
                $directSupervisorId = '';
            }

            /* 	$directownId = array($assocID);
              foreach($directownId as $valownid){
              $directownval[] = '-'.$valownid.'-';
              }
              $directownId = implode(',',$directownval); */

            //getting safety officer
            if (!empty($DriversafetyOfficerId)) {
                foreach ($DriversafetyOfficerId as $valsupervisorval) {
                    $DriversafetyOfficerval[] = '-' . $valsupervisorval . '-';
                }
                $DriversafetyOfficerId = implode(',', $DriversafetyOfficerval);
            } else {
                $DriversafetyOfficerId = '';
            }


            //$alertViewingUsers = $directownId;
            $alertViewingUsers = '';
            $alertViewingUsersId = '';
            if (!empty($directSupervisorId)) {
                $alertViewingUsers.=',' . $directSupervisorId;
                $alertViewingUsersId.=',' . $directSupervisorId;
            }

            if (!empty($DriversafetyOfficerId)) {
                $alertViewingUsers.=',' . $DriversafetyOfficerId;
                $alertViewingUsersId.=',' . $DriversafetyOfficerId;
            }

            //getting user email		
            if (!empty($alertViewingUsersId)) {
                $userIdArray = explode(',', $alertViewingUsersId);
                foreach ($userIdArray as $dKey => $dValue) {
                    $userId[] = str_replace("-", "", trim($dValue));
                }
            } else {
                $userId = '';
            }

            if (!empty($userId)) {
                $emailsArr = $this->User->find('list', array('fields' => 'user_email', 'conditions' => array('id' => $userId)));
            }
            //pr($emailsArr);die;

            if (!empty($userId)) {
                $emailsId = $this->User->find('list', array('fields' => 'id', 'conditions' => array('id' => $userId)));
            } else {
                $emailsId = '';
            }
            //pr($emailsId);die;
            //checking for existing alerts for this driver and driver and driver_evaluation_date
            $alertsArr = $this->Alert->find('all', array('conditions' => array('associate_to_id' => $assocID, 'Alert.alert_category_id' => $alert_categoryid, 'Alert.alert_type_id' => $alert_categoryTypeid, 'Alert.alert_module_id' => $alert_moduleid)));


            if (count($alertsArr)) {
                $newAlertArr['AlertLog'] = $alertsArr[0]['Alert'];
                //saving existing alerts to alert_logs table						
                $this->AlertLog->create(); // Create a new record
                unset($newAlertArr['AlertLog']['id']);
                $newAlertArr['AlertLog']['alert_id'] = $alertsArr[0]['Alert']['id'];
                $this->AlertLog->save($newAlertArr); // And save it					
            }
            $defaultDate = date('Y-m-d');
            $alertData['Alert']['alert_module_id'] = $alert_moduleid;
            $alertData['Alert']['alert_category_id'] = $alert_categoryid;
            $alertData['Alert']['alert_type_id'] = $alert_categoryTypeid;
            $alertData['Alert']['associate_to_model'] = 'User';
            $alertData['Alert']['driver_id'] = '';
            $alertData['Alert']['associate_to_id'] = $assocID;
            $alertData['Alert']['alert_generator_id'] = $loginid;
            $alertData['Alert']['alert_generator_usertype'] = $user_type;
            $alertData['Alert']['associated_branch_id'] = $data['User']['branch_id'];
            $alertData['Alert']['associated_department_id'] = $data['User']['department_id'];
            $alertData['Alert']['url'] = $url;
            $alertData['Alert']['priority'] = 1;
            $alertData['Alert']['task_subtask'] = 'task';
            $alertData['Alert']['date_due'] = $defaultDate;
            $alertData['Alert']['due_status'] = 1;
            $alertData['Alert']['percent_complete'] = '';
            $alertData['Alert']['project_status'] = 1;
            $alertData['Alert']['date_sent'] = $defaultDate;
            $alertData['Alert']['sent_users_id'] = $alertViewingUsers;
            $alertData['Alert']['viewed_users'] = 0;
            $alertData['Alert']['sent_users_count'] = $totalsentUsersCount;
            $alertData['Alert']['viewed_users_count'] = 0;
            $alertData['Alert']['date_created'] = $defaultDate;
            $alertData['Alert']['notice'] = '';
            $alertData['Alert']['message'] = $EmployeealertMessage;
            $alertData['Alert']['other_message'] = $OtheralertMessage;
            //pr($alertData);die;		
            if (count($alertsArr)) {
                $alertData['Alert']['id'] = $alertsArr[0]['Alert']['id'];
            }

            $this->Alert->create();
            if ($this->Alert->Save($alertData)) {
                if (!empty($emailsId)) {
                    foreach ($emailsId as $iduser) {
                        //pr($iduser);die;
                        $content = '';
                        $userArr = $this->User->find('first', array('conditions' => array('User.id' => $iduser)));
                        //pr($userArr);die;
                        if (count($userArr) && !empty(trim($userArr['User']['user_email']))) {
                            $username = !(empty($userArr)) ? $userArr['User']['user_fname'] : '';
                            //sending mail to Driver
                            $content.= '<p style="margin:0;padding:0;font-size:14px;color: #444444;font-weight:600;line-height:19px;font-weight:normal;">Hello ' . $username . '</p><p  style="margin:15px 0;padding:0;font-size:14px;color: #444444;font-weight:600;line-height:19px;font-weight:normal;">Welcome to SOAR Solutions.</p>';
                            $content.='<p style="margin:015px 0padding:0;font-size:14px;color: #444444;font-weight:600;line-height:19px;font-weight:normal;">Alert Message:' . $OtheralertMessage;

                            $subject = 'New Record Created';
                            $to = !(empty($userArr['User']['user_email'])) ? $userArr['User']['user_email'] : '';
                            if (!empty($to)) {
                                $this->sendMail($subject, $content, $to);
                            }
                        }
                    }
                }
            }
        }
    }

    public function NewAlertStatusInactive($assocID = null, $data = array()) {

        $defaultDate = date('Y-m-d');
        //pr($data);die;
        $loginUname = $this->Session->read('user_details');
        $user_type = $this->Session->read('user_type');
        if ($user_type == 'superAdmin') {
            $loginid = $this->Session->read('user_details.id');
        } elseif ($user_type == 'companyLogin') {
            $loginid = $this->Session->read('user_details.id');
        } else {
            $loginid = $this->Session->read('user_details.id');
        }

        $driverName = count($data) ? ucfirst($data['User']['user_fname']) . ' ' . ucfirst($data['User']['user_lname']) . ' (' . $data['User']['employee_id'] . ')' : '';

        $EmployeealertMessage = '';
        $OtheralertMessage = $driverName . ' is now inactive';

        $url = 'users/view_users/' . $assocID;

        $alert_moduleid = $this->add_alert_module('Employee');

        //saving alert category
        $alert_categoryid = $this->add_alert_category('User');
        //end 	
        //saving alert Type
        $alert_categoryTypeid = $this->add_alert_type($alert_categoryid, 'inactive', "Employee Status Inactive");
        //end 		
        //direct supervisor
        if (!empty($data['User']['user_supervisor'])) {
            $directSupervisorId = $data['User']['user_supervisor'];
        } else {
            $directSupervisorId = '';
        }

        if (!empty($directSupervisorId)) {
            $countsupervisor = count($directSupervisorId);
        } else {
            $countsupervisor = 0;
        }


        //Safety Officer
        $associatedsafety = $this->User->find('all', array('fields' => array('id'), 'conditions' => array('User.branch_id' => $data['User']['branch_id'], 'User.department_id' => $data['User']['department_id'], 'User.user_level' => 8)));


        foreach ($associatedsafety as $associds) {
            $associds = $associds['User']['id'];
            $SafetyOfficerId[] = $associds;
        }

        if (!empty($SafetyOfficerId)) {
            $DriversafetyOfficerId = $SafetyOfficerId;
        } else {
            $DriversafetyOfficerId = '';
        }
        //pr($DriversafetyOfficerId);die;					

        if (!empty($DriversafetyOfficerId)) {
            $DriversafetyOfficerIdcount = count($DriversafetyOfficerId);
        } else {
            $DriversafetyOfficerIdcount = 0;
        }


        $totalsentUsersCount = $countsupervisor + $DriversafetyOfficerIdcount;
        ;

        if (!empty($directSupervisorId)) {
            $directSupervisorId = array($directSupervisorId);
            foreach ($directSupervisorId as $valdirect) {
                $directSupervisorval[] = '-' . $valdirect . '-';
            }
            $directSupervisorId = implode(',', $directSupervisorval);
        } else {
            $directSupervisorId = '';
        }

        $directownId = array($assocID);
        foreach ($directownId as $valownid) {
            $directownval[] = '-' . $valownid . '-';
        }
        $directownId = implode(',', $directownval);

        //getting safety officer
        if (!empty($DriversafetyOfficerId)) {
            foreach ($DriversafetyOfficerId as $valsupervisorval) {
                $DriversafetyOfficerval[] = '-' . $valsupervisorval . '-';
            }
            $DriversafetyOfficerId = implode(',', $DriversafetyOfficerval);
        } else {
            $DriversafetyOfficerId = '';
        }


        $alertViewingUsers = '';
        $alertViewingUsersId = '';
        if (!empty($directSupervisorId)) {
            $alertViewingUsers.=',' . $directSupervisorId;
            $alertViewingUsersId.=',' . $directSupervisorId;
        }

        if (!empty($DriversafetyOfficerId)) {
            $alertViewingUsers.=',' . $DriversafetyOfficerId;
            $alertViewingUsersId.=',' . $DriversafetyOfficerId;
        }

        //getting user email		
        if (!empty($alertViewingUsersId)) {
            $userIdArray = explode(',', $alertViewingUsersId);
            foreach ($userIdArray as $dKey => $dValue) {
                $userId[] = str_replace("-", "", trim($dValue));
            }
        } else {
            $userId = '';
        }

        if (!empty($userId)) {
            $emailsArr = $this->User->find('list', array('fields' => 'user_email', 'conditions' => array('id' => $userId)));
        }
        //pr($emailsArr);die;

        if (!empty($userId)) {
            $emailsId = $this->User->find('list', array('fields' => 'id', 'conditions' => array('id' => $userId)));
        } else {
            $emailsId = '';
        }

        //checking for existing alerts for this driver and driver and driver_evaluation_date
        $alertsArr = $this->Alert->find('all', array('conditions' => array('associate_to_id' => $assocID, 'Alert.alert_category_id' => $alert_categoryid, 'Alert.alert_type_id' => $alert_categoryTypeid, 'Alert.alert_module_id' => $alert_moduleid)));
        //pr($alertsArr);

        if (count($alertsArr)) {
            $newAlertArr['AlertLog'] = $alertsArr[0]['Alert'];
            //saving existing alerts to alert_logs table						
            $this->AlertLog->create(); // Create a new record
            unset($newAlertArr['AlertLog']['id']);
            $newAlertArr['AlertLog']['alert_id'] = $alertsArr[0]['Alert']['id'];
            $this->AlertLog->save($newAlertArr); // And save it					
        }
        $defaultDate = date('Y-m-d');
        $alertData['Alert']['alert_module_id'] = $alert_moduleid;
        $alertData['Alert']['alert_category_id'] = $alert_categoryid;
        $alertData['Alert']['alert_type_id'] = $alert_categoryTypeid;
        $alertData['Alert']['associate_to_model'] = 'User';
        $alertData['Alert']['driver_id'] = '';
        $alertData['Alert']['associate_to_id'] = $assocID;
        $alertData['Alert']['alert_generator_id'] = $loginid;
        $alertData['Alert']['alert_generator_usertype'] = $user_type;
        $alertData['Alert']['associated_branch_id'] = $data['User']['branch_id'];
        $alertData['Alert']['associated_department_id'] = $data['User']['department_id'];
        $alertData['Alert']['url'] = $url;
        $alertData['Alert']['priority'] = 1;
        $alertData['Alert']['task_subtask'] = 'task';
        $alertData['Alert']['date_due'] = $defaultDate;
        $alertData['Alert']['due_status'] = 1;
        $alertData['Alert']['percent_complete'] = '';
        $alertData['Alert']['project_status'] = 1;
        $alertData['Alert']['date_sent'] = $defaultDate;
        $alertData['Alert']['sent_users_id'] = $alertViewingUsers;
        $alertData['Alert']['viewed_users'] = 0;
        $alertData['Alert']['sent_users_count'] = $totalsentUsersCount;
        $alertData['Alert']['viewed_users_count'] = 0;
        $alertData['Alert']['date_created'] = $defaultDate;
        $alertData['Alert']['notice'] = '';
        $alertData['Alert']['message'] = $EmployeealertMessage;
        $alertData['Alert']['other_message'] = $OtheralertMessage;
        //pr($alertData);die;		

        if (count($alertsArr)) {
            $alertData['Alert']['id'] = $alertsArr[0]['Alert']['id'];
        }

        $this->Alert->create();
        if ($this->Alert->Save($alertData)) {
            if (!empty($emailsId)) {
                foreach ($emailsId as $iduser) {
                    //	pr($iduser);die;
                    $content = '';
                    $userArr = $this->User->find('first', array('conditions' => array('User.id' => $iduser)));
                    if (count($userArr) && !empty(trim($userArr['User']['user_email']))) {
                        $username = !(empty($userArr)) ? $userArr['User']['user_fname'] : '';
                        //sending mail to Driver
                        $content.= '<p style="margin:0;padding:0;font-size:14px;color: #444444;font-weight:600;line-height:19px;font-weight:normal;">Hello ' . $username . '</p><p  style="margin:15px 0;padding:0;font-size:14px;color: #444444;font-weight:600;line-height:19px;font-weight:normal;">Welcome to SOAR Solutions.</p>';
                        $content.='<p style="margin:015px 0padding:0;font-size:14px;color: #444444;font-weight:600;line-height:19px;font-weight:normal;">Alert Message:' . $OtheralertMessage;

                        $subject = 'Status Changed';
                        //$to = !(empty($userArr))?$userArr['User']['user_email']:'';
                        $to = !(empty($userArr['User']['user_email'])) ? $userArr['User']['user_email'] : '';
                        //$to=$userArr['User']['user_email'];
                        if (!empty($to)) {
                            $this->sendMail($subject, $content, $to);
                        }
                    }
                }
            }
        }
    }

//getting export function condition array
    function exportcondition() {
        //Get the User's access Branch list
        $accessBranchList = $this->_getAccessBranchListArray();
        $accessDeptList = $this->_getAccessDeptListArray();
        $conditions = array();

        //$conditions = array();
        if ($accessBranchList != 'all') {
            $branchCndnt = array('User.branch_id' => $accessBranchList);
            $conditions = array_merge($conditions, $branchCndnt);
        }

        //$conditions = array();
        if ($accessDeptList != 'all') {
            $deptsCndnt = array('User.department_id' => $accessDeptList);
            $conditions = array_merge($conditions, $deptsCndnt);
        }


        //pr($conditions);
        //if($this->Session->read('Search.users.filterData')!=''){
        if ($this->Session->read('filterData') != '') {
            //$this->Session->write('filterData',$this->Session->read('Search.vendors.filterData'));

            if ($this->Session->read('filterData.branch_list') != '') {
                $brachCndnt = array('User.branch_id' => $this->Session->read('filterData.branch_list'));
                $conditions = array_merge($conditions, $brachCndnt);
            }

            if ($this->Session->read('filterData.status_list') != '') {
                $statusCndnt = array('User.user_status' => $this->Session->read('filterData.status_list'));
                $conditions = array_merge($conditions, $statusCndnt);
            }


            $this->User->bindModel(array('belongsTo' => array(
                    'Branch' => array(
                        'foreignKey' => false,
                        'conditions' => array('User.branch_id = Branch.id')),
                    'Department' => array(
                        'foreignKey' => false,
                        'conditions' => array('User.department_id = Department.id')
                    ),
                    'UserType' => array(
                        'foreignKey' => false,
                        'conditions' => array('User.user_level = UserType.id')
                    )
                ),
                    ), false);
            if (trim($this->Session->read('filterData.searchkeyword')) != 'Search Employee' && trim($this->Session->read('filterData.searchkeyword')) != '') {
                $searchKeywordArr = explode(' ', trim($this->Session->read('filterData.searchkeyword')));
                $OriginalSearchArr = array($this->Session->read('filterData.searchkeyword'));
                $searchKeywordArr = array_merge($searchKeywordArr, $OriginalSearchArr);
                $completeSearchArr = $this->__getSearchCritiera($searchKeywordArr, array('User', 'Branch', 'Department', 'UserType'));
                $keywordCndnt = array('OR ' => $completeSearchArr);
                $conditions = array_merge($conditions, $keywordCndnt);
            }
        }
        return $conditions;
    }

//ends here
    public function exportFunction() {

        if ($this->request->is(array('post', 'put'))) {

            $exportData = $this->data;
            $sortingorder = $this->data['User']['sortingorder'];
            $sortingby = $this->data['User']['sortby'];
            //getting condition array
            $conditions = $this->exportcondition();
            $userFullName = $this->_getEmpName($this->Session->read('user_details.id'));

            if (!empty($exportData['User']['current_page'])) {
                $currentpage = $exportData['User']['current_page'];
            } else {
                $currentpage = '';
            }
            //end here
            //creating and saving csv file
            if (($exportData['User']['file_type'] == 'csv')) {
                $userinfo = array();
                if ($exportData['User']['exporttype'] == 'all_data_csv') {
                    $userdetails = $this->User->find('all', array('order' => array('User.' . $sortingby => $sortingorder)));
                } elseif ($exportData['User']['exporttype'] == 'list_data_csv') {
                    $userdetails = $this->User->find('all', array('conditions' => $conditions, 'order' => array('User.' . $sortingby => $sortingorder)));
                }
                if (count($userdetails)) {
                    foreach ($userdetails as $detailKey => $data) {
                        //pr($data);
                        $userinfo[$detailKey]['User']['name'] = $this->_getEmpName($data['User']['id']);
                        $userinfo[$detailKey]['User']['branch'] = $this->_getBranchName($data['User']['branch_id']);
                        $userinfo[$detailKey]['User']['department'] = $this->_getDepartmentName($data['User']['department_id']);
                        $activeAlerts = $this->Alert->find('count', array('conditions' => array('Alert.sent_users_id like' => '%-' . $data['User']['id'] . '-%', 'Alert.viewed_users <>' => '%-' . $data['User']['id'] . '-%')));
                        $userinfo[$detailKey]['User']['active_alerts'] = $activeAlerts;
                        $userinfo[$detailKey]['User']['permission'] = $data['User']['user_login_permission'];
                        $userinfo[$detailKey]['User']['status_name'] = $this->_getStatusName($data['User']['user_status']);
                    }
                }

                //if user email checkbox is not checked then directly download the csv file to user
                if (!isset($exportData['email'])) {
                    $this->layout = 'ajax';
                    $this->response->download("Employee-Csv-Listing-export.csv");
                    $this->set(compact('userinfo'));
                    return;
                } else {
                    //else create a csv file,save and attach with user email

                    $filename = 'exportCsv_' . time() . '.csv';
                    $filepath = dirname(APP) . '/app/webroot/export/' . $filename;
                    ob_start();
                    $headerArr = array('Name', 'Branch', 'Department', 'Active Alerts', 'Permission', 'Status');
                    echo implode(',', $headerArr) . "\n";
                    if (count($userdetails)) {
                        foreach ($userinfo as $row):
                            foreach ($row['User'] as &$cell):
                                $cell = '"' . preg_replace('/"/', '""', $cell) . '"';
                            endforeach;
                            echo implode(',', $row['User']) . "\n";
                        endforeach;
                    }
                    $output_so_far = ob_get_contents();
                    ob_clean();
                    file_put_contents($filepath, $output_so_far);
                    $to = $exportData['User']['emailuser'];
                    $content = 'Hi,<br/>' . $userFullName . ' has emailed you the attached file from mySOAR Solutions. ';
                    $attachments = WWW_ROOT . 'export/' . $filename;
                    $this->sendMailwithAttachments('Employee Csv export', $content, $to, $attachments);
                    //getting unlink or remove file from server
                    unlink($attachments);
                    $this->Session->setFlash(__("Employee Exported list has been sent to email successfully."));
                }
            }

            //csv file code ends here
            //creating and saving excel file 
            if ($exportData['User']['file_type'] == 'xls') {

                $userinfo = array();
                $userdetails = array();
                if ($exportData['User']['exporttype'] == 'all_data_xls') {
                    $userdetails = $this->User->find('all', array('order' => array('User.' . $sortingby => $sortingorder)));
                } elseif ($exportData['User']['exporttype'] == 'current_list_xls') {
                    $userdetails = $this->User->find('all', array('conditions' => $conditions, 'order' => array('User.' . $sortingby => $sortingorder)));
                }

                if (count($userdetails)) {
                    foreach ($userdetails as $detailKey => $data) {
                        $userinfo[$detailKey]['User']['name'] = $this->_getEmpName($data['User']['id']);
                        $userinfo[$detailKey]['User']['branch'] = $this->_getBranchName($data['User']['branch_id']);
                        $userinfo[$detailKey]['User']['department'] = $this->_getDepartmentName($data['User']['department_id']);
                        $activeAlerts = $this->Alert->find('count', array('conditions' => array('Alert.sent_users_id like' => '%-' . $data['User']['id'] . '-%', 'Alert.viewed_users <>' => '%-' . $data['User']['id'] . '-%')));
                        $userinfo[$detailKey]['User']['active_alerts'] = $activeAlerts;
                        $userinfo[$detailKey]['User']['permission'] = $data['User']['user_login_permission'];
                        $userinfo[$detailKey]['User']['status_name'] = $this->_getStatusName($data['User']['user_status']);
                    }
                }

                //if user email checkbox is not checked then directly download the csv file to user
                if (!isset($exportData['email'])) {
                    $this->layout = 'ajax';
                    $this->response->download("Employee-Csv-Listing-export.xls");
                    $this->set(compact('userinfo'));
                    return;
                } else {
                    //else create a excel file,save and attach with user email			
                    $filename = 'employee_exportXls_' . time() . '.xls';
                    $filepath = dirname(APP) . '/app/webroot/export/' . $filename;
                    ob_start();
                    $headerArr = array('Name', 'Branch', 'Department', 'Active Alerts', 'Permission', 'Status');
                    echo implode(',', $headerArr) . "\n";
                    if (count($userdetails)) {
                        foreach ($userinfo as $row):
                            foreach ($row['User'] as &$cell):
                                $cell = '"' . preg_replace('/"/', '""', $cell) . '"';
                            endforeach;
                            echo implode(',', $row['User']) . "\n";
                        endforeach;
                    }
                    $output_so_far = ob_get_contents();
                    ob_clean();
                    file_put_contents($filepath, $output_so_far);
                    $to = $exportData['User']['emailuser'];
                    $content = 'Hi,<br/>' . $userFullName . ' has emailed you the attached file from mySOAR Solutions. ';
                    $attachments = WWW_ROOT . 'export/' . $filename;
                    $this->sendMailwithAttachments('Employee Excel export', $content, $to, $attachments);
                    //getting unlink or remove file from server
                    unlink($attachments);
                    $this->Session->setFlash(__("Employee Exported list has been sent to email successfully."));
                }
            }
            //excel file code ends here
            //creating and saving excel file 
            if ($exportData['User']['file_type'] == 'xml') {
                $userinfo = array();
                $this->layout = 'pdf';

                if ($exportData['User']['exporttype'] == 'current_list_xml') {
                    $userdetails = $this->User->find('all', array('conditions' => $conditions, 'order' => array('User.' . $sortingby => $sortingorder)));
                } elseif ($exportData['User']['exporttype'] == 'all_data_xml') {

                    $userdetails = $this->User->find('all', array('order' => array('User.' . $sortingby => $sortingorder)));
                }

                if (count($userdetails)) {
                    foreach ($userdetails as $detailKey => $data) {
                        $userinfo[$detailKey]['User']['name'] = $this->_getEmpName($data['User']['id']);
                        $userinfo[$detailKey]['User']['branch'] = $this->_getBranchName($data['User']['branch_id']);
                        $userinfo[$detailKey]['User']['department'] = $this->_getDepartmentName($data['User']['department_id']);
                        $activeAlerts = $this->Alert->find('count', array('conditions' => array('Alert.sent_users_id like' => '%-' . $data['User']['id'] . '-%', 'Alert.viewed_users <>' => '%-' . $data['User']['id'] . '-%')));
                        $userinfo[$detailKey]['User']['active_alerts'] = $activeAlerts;
                        $userinfo[$detailKey]['User']['permission'] = $data['User']['user_login_permission'];
                        $userinfo[$detailKey]['User']['status_name'] = $this->_getStatusName($data['User']['user_status']);
                    }
                }
                //if user email checkbox is not checked then directly download the csv file to user
                if (!isset($exportData['email'])) {


                    $this->set('data', $userinfo);
                    $this->set('status', 'download');
                    $this->render('exportpdf');
                } else {
                    //else create a excel file,save and attach with user email			
                    $filename = 'employee_exportPdf_' . time() . '.pdf';
                    $filepath = dirname(APP) . '/app/webroot/export/' . $filename;

                    $this->set('data', $userinfo);
                    $this->set('filepath', $filepath);
                    $this->set('status', 'save');
                    $this->render('exportpdf');
                    $to = $exportData['User']['emailuser'];
                    $content = 'Hi,<br/>' . $userFullName . ' has emailed you the attached file from mySOAR Solutions. ';
                    $attachments = WWW_ROOT . 'export/' . $filename;
                    $this->sendMailwithAttachments('Employee Pdf export', $content, $to, $attachments);
                    //getting unlink or remove file from server
                    unlink($attachments);
                    $this->Session->setFlash(__("Employee Exported list has been sent to email successfully."));
                }
            }
            //excel file code ends here				
        }
        $this->redirect(array('controller' => 'users', 'action' => 'index'));
    }

    function exportxml() {
        $sortingorder = $this->data['User']['sortingorder'];
        $sortingby = $this->data['User']['sortby'];
        $userdetails = array();
        $userinfo = array();
        $this->layout = "ajax";
        $exportData = $this->data;
        //getting condition array
        $conditions = $this->exportcondition();
        $userFullName = $this->_getEmpName($this->Session->read('user_details.id'));
        //getting the current page 
        if (!empty($exportData['User']['current_page'])) {
            $currentpage = $exportData['User']['current_page'];
        } else {
            $currentpage = '';
        }
        //end here

        if ($exportData['User']['exporttype'] == 'current_list_xml') {
            $userdetails = $this->User->find('all', array('conditions' => $conditions, 'order' => array('User.' . $sortingby => $sortingorder)));
        } elseif ($exportData['User']['exporttype'] == 'all_data_xml') {
            $userdetails = $this->User->find('all', array('order' => array('User.' . $sortingby => $sortingorder)));
        }
        if (count($userdetails)) {
            foreach ($userdetails as $detailKey => $data) {
                $userinfo[$detailKey]['User']['name'] = $this->_getEmpName($data['User']['id']);
                $userinfo[$detailKey]['User']['branch'] = $this->_getBranchName($data['User']['branch_id']);
                $userinfo[$detailKey]['User']['department'] = $this->_getDepartmentName($data['User']['department_id']);
                $activeAlerts = $this->Alert->find('count', array('conditions' => array('Alert.sent_users_id like' => '%-' . $data['User']['id'] . '-%', 'Alert.viewed_users <>' => '%-' . $data['User']['id'] . '-%')));
                $userinfo[$detailKey]['User']['active_alerts'] = $activeAlerts;
                $userPermission = $data['User']['user_login_permission'];
                if ($userPermission == 'yes') {
                    $permission = 'yes';
                } else {
                    $permission = 'No';
                }
                $userinfo[$detailKey]['User']['permission'] = $userPermission;
                $userinfo[$detailKey]['User']['status_name'] = $this->_getStatusName($data['User']['user_status']);
            }
        }
        //if user email checkbox is not checked then directly download the xml file to user
        if (!isset($exportData['email'])) {
            $this->response->download("export.xml");
            $this->set('filestatus', 'download');
            $this->set('data', $userinfo);
            return;
        } else {
            //else create a excel file,save and attach with user email			
            $filename = 'exportXml_' . time() . '.xml';
            $filepath = dirname(APP) . '/app/webroot/export/' . $filename;
            $this->set('data', $userinfo);
            $this->set('filepath', $filepath);
            $this->set('filestatus', 'save');
            $this->render('exportxml');
            $to = $exportData['User']['emailuser'];
            $content = 'Hi,<br/>' . $userFullName . ' has emailed you the attached file from mySOAR Solutions. ';
            $attachments = WWW_ROOT . 'export/' . $filename;
            $this->sendMailwithAttachments('Employee Xml export', $content, $to, $attachments);
            //getting unlink or remove file from server
            unlink($attachments);

            $this->Session->setFlash(__("Employee Exported list has been sent to email successfully."));
        }
        $this->redirect(array('controller' => 'users', 'action' => 'index'));
    }

//function to send pdf on mail
    function pdfonmail($id = NULL) {
        $userFullName = $this->_getEmpName($this->Session->read('user_details.id'));
        $filename = 'exportPdf_' . time() . '.pdf';
        $filepath = dirname(APP) . '/app/webroot/export/' . $filename;
        $userDetails = $this->User->find('first', array('conditions' => array('User.id' => $id)));
        $userDetails['User']['branch_name'] = $this->_getBranchName($userDetails['User']['branch_id']);
        $userDetails['User']['department_name'] = $this->_getDepartmentName($userDetails['User']['department_id']);


        $userDetails['User']['user_is_supervisor_fname'] = $this->_getEmpName($userDetails['User']['id']);
        $userDetails['User']['user_status_name'] = $this->_getStatusName($userDetails['User']['user_status']);
        $userDetails['User']['user_level_name'] = $this->_getUserType($userDetails['User']['user_level']);
        $userDetails['User']['direct_supervisor_name'] = $this->_getEmpName($userDetails['User']['user_supervisor']);
        $userDetails['User']['user_start_date'] = $this->_dateFormat($userDetails['User']['user_start_date']);
        $userDetails['User']['user_end_date'] = $this->_dateFormat($userDetails['User']['user_end_date']);
        $userDetails['User']['created_date'] = $this->_dateFormat($userDetails['User']['created_date']);
        $userDetails['User']['closed_date'] = $this->_dateFormat($userDetails['User']['closed_date']);

        if (!empty($userDetails['User']['userjobfuntion_id'])) {
            $new_str = str_replace("-", '', $userDetails['User']['userjobfuntion_id']);
            $userJobtypeID = explode(',', $new_str);
            $userJobList = $this->JobFunction->find('list', array('fields' => array('job_function'), 'conditions' => array('JobFunction.id' => $userJobtypeID)));

            $userDetails['User']['job_type'] = implode(', ', $userJobList);
        } else {
            $userDetails['User']['job_type'] = '';
        }
        if (!empty($userDetails['User']['user_branches'])) {
            $userBranchesID = explode(',', $userDetails['User']['user_branches']);
            $branches = $this->Branch->find('list', array('fields' => array('branch_name'), 'conditions' => array('Branch.id' => $userBranchesID)));
            $userDetails['User']['user_branches_names'] = implode(',', $branches);
        } else {
            $userDetails['User']['user_branches_names'] = '';
        }

        if (!empty($userDetails['User']['user_departments'])) {
            $userdepID = explode(',', $userDetails['User']['user_departments']);
            $departmentsArr = $this->Department->find('all', array('fields' => array('department_name', 'branch_id'), 'conditions' => array('Department.id' => $userdepID)));

            if (count($departmentsArr)) {
                foreach ($departmentsArr as $key => $deptArr) {
                    $branchname = $this->_getBranchName($deptArr['Department']['branch_id']);
                    $departments[] = $deptArr['Department']['department_name'] . '(' . $branchname . ')';
                }
                $userDetails['User']['user_departments_names'] = implode(',', $departments);
            } else {
                $userDetails['User']['user_departments_names'] = '';
            }
        } else {
            $userDetails['User']['user_departments_names'] = '';
        }

        $this->set('data', $userDetails);
        $this->set('filepath', $filepath);
        $this->render();
        //send mail
        $to = $this->data['User']['emailuser'];
        $content = 'Hi,<br/>' . $userFullName . ' has emailed you the attached file from mySOAR Solutions. ';
        $attachments = WWW_ROOT . 'export/' . $filename;
        $this->sendMailwithAttachments('Employee Pdf export', $content, $to, $attachments);
        //getting unlink or remove file from server
        unlink($attachments);
        $this->Session->setFlash(__("Pdf has been sent to email successfully."));
        //end mail
        $this->redirect(array('controller' => 'users', 'action' => 'view_users', $id));
    }

//date format function
    public function dateFormat($date = Null) {
        if ($date == '') {
            $dateVal = '';
        } else {
            $s = $date;
            $date = strtotime($s);
            $dateVal = date('M d, Y', $date);
        }
        return $dateVal;
    }

//date format function
    //new work for employee and supervisor dashboard
    //setting session for supervisor dashboard 
    public function filterScorecard() {

        if (!empty($this->data)) {
            //$this->Session->write('Search.users.filterData',$this->data['User']);
            $this->Session->write('filterScorecard', $this->data['User']);
            return $this->redirect(array('action' => 'supervisor'));
        }
        return $this->redirect(array('action' => 'supervisor'));
    }

    //end
    //employee individual dashboard
    public function individual() {
        
		//getting the training records        
        $currentDate		= date('Y-m-d');
        $loggedinUserid = $this->Session->read('user_details.id');
		//echo "<pre>"; print_r ($this->data);
		if (empty($this->data))  {
			$id	=	$loggedinUserid;
		}  else  {
			$id 	= $this->data['Training']['employee_id'];
		}		
		
		$conditions 		= $this->trainings();
        $conditions 		= array_merge($conditions, array('Training.employee_id' => $id));
		//1- All the training records will be on calendar and list that are not expired and not completed. 
        $conditions 		= array_merge($conditions, array('OR' => array('Training.date_expired >=' => $currentDate, 'Training.training_completed' => 'no')));
        
		$details 			= $this->Training->find(
			'all', array(
				'fields' => array(
					'Training.date_expired', 
					'TrainingType.name'
				), 
				'order' => array(
					'Training.id' => 'desc'
				), 
				'conditions' => $conditions
			)
		);
		
		//echo "<pre>"; print_r($details);die;
        
		$trainingArr 		= array();
        foreach ($details as $Key => $Val) {
            $trainingArr[$Key]['type'] = $Val['TrainingType']['name'];
            $trainingArr[$Key]['expire'] = $this->_dateFormat($Val['Training']['date_expired']);
        }
        //end
		
        //getting all the action items and subtasks
        $actionitems 	= $this->Actionitem->find(
			'all', array (
				'conditions' => array (
					'Actionitem.date_due >=' => $currentDate, 
					'OR' => array(
						'Actionitem.date_due <>' => NULL, 
						'Actionitem.date_due <>' => '0000-00-00'
					)
				), 
				'recursive' => 2
			)
		);
		
        $actionitemSubtasks	= $this->ActionitemSubtask->find(
			'all', array (
				'conditions' => array (
					'ActionitemSubtask.subtask_date_due >=' => $currentDate, 
					'OR' => array (
						'ActionitemSubtask.subtask_date_due <>' => NULL, 
						'ActionitemSubtask.subtask_date_due <>' => '0000-00-00'
					)
				), 
				'recursive' => 2
			)
		);
		
        // pr($actionitemSubtasks);
        $actionitemArr = array();
        foreach ($actionitems as $actionitem) {
            $dataArr = array();
            $dataArr['title'] = $actionitem['ActionitemCategory']['name'];
            $dataArr['start'] = $this->_dateFormat($actionitem['Actionitem']['date_due']);
            array_push($actionitemArr, $dataArr);
        }

        foreach ($actionitemSubtasks as $actionitemSubtask) {
            $dataArr = array();
            $dataArr['title'] = $actionitemSubtask['ActionitemType']['name'];
            $dataArr['start'] = $this->_dateFormat($actionitemSubtask['ActionitemSubtask']['subtask_date_due']);
            array_push($actionitemArr, $dataArr);
        }
        //end       		
		
		$total_trainings = $this->Training->find(
			'first', array(
			  'fields' => array(
					'(SUM(CASE WHEN Training.id > 1 THEN 1 ELSE 0 END)) AS total_training',
					'(SUM(CASE WHEN Training.training_completed =  "yes" THEN 1 ELSE 0 END)) AS complete',
					'(SUM(CASE WHEN Training.training_completed = "no" THEN 1 ELSE 0 END)) AS incomplete',
					'(SUM(CASE WHEN Training.date_expired < CURDATE() THEN 1 ELSE 0 END)) AS date_expired',
					'(SUM(CASE WHEN Training.related_to = "accident" THEN 1 ELSE 0 END)) AS accident',
					'(SUM(CASE WHEN Training.related_to = "safety" THEN 1 ELSE 0 END)) AS safety',
					'(SUM(CASE WHEN Training.related_to = "ticket" THEN 1 ELSE 0 END)) AS ticket',
					'(SUM(CASE WHEN Training.related_to = "warning" THEN 1 ELSE 0 END)) AS warning',
			   ),
			   'contain'=>array(),
			   'conditions' => array('Training.employee_id' => $id),
			)
		);
		
		//echo "<pre>"; print_r($total_trainings);		
		//echo "<pre>"; print_r($total_training);
		//die;
		
		$empID = $this->User->find('list');

        $empList = $this->_getEmployeeList(implode(',', $empID));

        foreach ($empList as $val) {

            $empList[$val] = $this->User->find('list', array('fields' => array('user_lname', 'user_fname', 'employee_id'), 'conditions' => array('User.id' => $val)));
        }
		
		//echo "<pre>"; print_r ($empList);die;
		
        $this->set('id', $id);
        $this->set('empList', $empList);
        $this->set('total_trainings', $total_trainings);
        $this->set('trainingArr', $trainingArr);
        $this->set('actionitems', $actionitemArr);
    }

    //supervisor dashboard function
    public function supervisor() {



        $branchList = $this->_getBranchesList();

        //getting the training records
        $trainingConditions = array();
        $trainingConditions = $this->trainings();
        $currentDate = date('Y-m-d');
        $trainingConditions = array_merge($trainingConditions, array('Training.date_expired >=' => $currentDate, 'training_completed' => 'no'));
        $details = $this->Training->find('all', array('fields' => array('User.user_fname', 'User.user_lname', 'User.id', 'Training.date_expired', 'TrainingType.name'), 'order' => array('Training.id' => 'desc'), 'conditions' => $trainingConditions));
        $trainingArr = array();

        foreach ($details as $Key => $Val) {

            $trainingArr[$Key]['title'] = $Val['TrainingType']['name'];
            $trainingArr[$Key]['name'] = $Val['User']['user_fname'];
            $trainingArr[$Key]['start'] = $this->_dateFormat($Val['Training']['date_expired']);
        }
        //end
        //getting all the action items and subtasks
        $actionitems = $this->Actionitem->find('all', array('conditions' => array('Actionitem.date_due >=' => $currentDate, 'OR' => array('Actionitem.date_due <>' => NULL, 'Actionitem.date_due <>' => '0000-00-00')), 'recursive' => 2));
        $actionitemSubtasks = $this->ActionitemSubtask->find('all', array('conditions' => array('ActionitemSubtask.subtask_date_due >=' => $currentDate, 'OR' => array('ActionitemSubtask.subtask_date_due <>' => NULL, 'ActionitemSubtask.subtask_date_due <>' => '0000-00-00')), 'recursive' => 2));

        $actionitemArr = array();
        foreach ($actionitems as $actionitem) {

            $dataArr = array();
            $dataArr['name'] = !empty($actionitem['User']['user_fname']) ? $actionitem['User']['user_fname'] : '';
            $dataArr['title'] = $actionitem['ActionitemCategory']['name'];
            $dataArr['start'] = $this->_dateFormat($actionitem['Actionitem']['date_due']);

            array_push($actionitemArr, $dataArr);
        }

        foreach ($actionitemSubtasks as $actionitemSubtask) {

            $dataArr = array();
            $dataArr['name'] = !empty($actionitemSubtask['Actionitem']['User']['user_fname']) ? $actionitemSubtask['Actionitem']['User']['user_fname'] : '';
            $dataArr['title'] = $actionitemSubtask['ActionitemType']['name'];
            $dataArr['start'] = $this->_dateFormat($actionitemSubtask['ActionitemSubtask']['subtask_date_due']);

            array_push($actionitemArr, $dataArr);
        }
        //end  
        //getting the scorecard data
        //getting all users under this supervisor                           
        $accessBranchList = $this->_getAccessBranchListArray();
        $accessDeptList = $this->_getAccessDeptListArray();
        $conditions = array();

        //$conditions = array();
        if ($accessBranchList != 'all') {
            $branchCndnt = array('User.branch_id' => $accessBranchList);
            $conditions = array_merge($conditions, $branchCndnt);
        }

        //$conditions = array();
        if ($accessDeptList != 'all') {
            $deptsCndnt = array('User.department_id' => $accessDeptList);
            $conditions = array_merge($conditions, $deptsCndnt);
        }
        if ($this->Session->read('filterScorecard') != '') {

            if ($this->Session->read('filterScorecard.branch_list') != '') {
                $brachCndnt = array('User.branch_id' => $this->Session->read('filterScorecard.branch_list'));
                $conditions = array_merge($conditions, $brachCndnt);
            } else {
                $this->Session->delete('filterData');
            }
        }
        $this->Paginator->settings = array(
            'conditions' => $conditions,
            'limit' => 10000000000,
            'order' => array(
                'User.id' => 'desc'
            )
        );
        $userArr = $this->Paginator->paginate('User');

        if (!empty($this->params->named)) {
            $sortBy = $this->params->named['sort'];
            //$order  = ($this->params->named['direction']=='asc')?SORT_AS:SORT_DESC;
        }

        $userdetails = $sortByArr = array();
        $currentDate = date('Y-m-d');
        foreach ($userArr as $userKey => $userData) {
            $userdetails[$userKey]['name'] = $this->_getEmpName($userData['User']['id']);
            $userdetails[$userKey]['department'] = $this->_getDepartmentName($userData['User']['department_id']);

            //getting the complete,incomplete,expire training
            $userdetails[$userKey]['completed'] = $this->Training->find('count', array('conditions' => array('training_completed' => 'yes', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['incompleted'] = $this->Training->find('count', array('conditions' => array('training_completed' => 'no', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['expired'] = $this->Training->find('count', array('conditions' => array('date_expired <' => $currentDate, 'Training.employee_id' => $userData['User']['id'])));

            //count the tranings for accident,warning,safety,ticket
            $userdetails[$userKey]['accident'] = $this->Training->find('count', array('conditions' => array('related_to' => 'accident', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['warning'] = $this->Training->find('count', array('conditions' => array('related_to' => 'warning', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['safety'] = $this->Training->find('count', array('conditions' => array('related_to' => 'safety', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['ticket'] = $this->Training->find('count', array('conditions' => array('related_to' => 'ticket', 'Training.employee_id' => $userData['User']['id'])));
        }




        //sorting the array
        if (!empty($this->params->named)) {
            foreach ($userdetails as $key => $data) {
                $sortByArr[$key] = $data[$sortBy];
            }

            if ($this->params->named['direction'] == 'asc') {

                array_multisort($sortByArr, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $userdetails);
            } else {
                array_multisort($sortByArr, SORT_DESC, SORT_NATURAL | SORT_FLAG_CASE, $userdetails);
            }
        }




        //end
        $this->set('userdetails', $userdetails);
        $this->set('branchList', $branchList);
        $this->set('trainingArr', $trainingArr);
        $this->set('actionitems', $actionitemArr);
        $this->set('filterScorecard', $this->Session->read('filterScorecard'));
    }

    //function to get data for calander
	/*
		Color Codes : 
			FF0000 => Red ,
			257e4a => Green,
			3090C7	=> Blue
	*/
    function calanderData($actiontype = NULL,$id=null)  {			
		Configure::write('debug','true');
        $this->layout 		= false;
        $this->autoRender	= false;
		$trainingArr 			= array();
        $currentDate 		= date('Y-m-d');

        //get training records
		//START
		$loggedinUserid = $this->Session->read('user_details.id');
        $conditions 		= $this->trainings();
		
        $conditions 		=	array_merge($conditions, array('Training.date_expired >' => '0000:00:00'));
		//$conditions		=	array_merge($conditions ,array('AND'=>array('Training.date_expired >' =>$currentDate,'Training.training_completed' =>'no')));
		
        if ($actiontype == 'individual')  {            
            $conditions = array_merge($conditions, array('Training.employee_id' => $loggedinUserid));
        }

        $details = $this->Training->find('all', array('order' => array('Training.id' => 'desc'), 'conditions' => $conditions,'contain'=>array('TrainingType')));
		//echo "<pre>"; print_r ($details);die;
		
        
        foreach ($details as $Key => $Val)  {

            $trainingArr[$Key]['title']	= $Val['TrainingType']['name'];
            $trainingArr[$Key]['start'] = isset($Val['Training']['date_expired']) ? $Val['Training']['date_expired'] : '0000:00:00';

			$trainingArr[$Key]['color'] = '#3090C7';
			
			// 2- if the record is completed it will be green with completed date. It will be green up to 30 days prior to expired date or due date.
			if ($Val['Training']['training_completed'] == 'yes')  {
				$trainingArr[$Key]['title']		= $Val['TrainingType']['name'].' '.$Val['Training']['date_completed'];
                $trainingArr[$Key]['color'] 	= '#257e4a';				
            }  
			
			// 3- If a training record, action item/subtask is complete and not expired, it will display on the calendar in red, and on the list 30 days prior to expired. It comes off the list and switches back to green on the calendar when a new training or action item/subtask record is added to the system with the same training type or action type/subtask, employee, and new expired date is greater than the current expired date.
			elseif ($Val['Training']['training_completed'] == 'yes' and (strtotime($Val['Training']['date_expired']) > strtotime($currentDate)))  {
				$trainingArr[$Key]['color'] 	= '#FF0000';				
			}   
			//4- If a training, action/subtask is complete and expired and there is not a more recent training or action item to superceed, it will display on the calendar in red and on the list. It comes off the list and switches back to green on the calendar when a new training or action item/subtask record is added to the system with the same training type or action type/subtask, employee, and new expired date is greater than the current expired date.
			elseif ($Val['Training']['training_completed'] == 'yes' and (strtotime($Val['Training']['date_expired']) < strtotime($currentDate)))  {
				$trainingArr[$Key]['color'] 	= '#257e4a';				
			}   
			
			elseif ($Val['Training']['training_completed'] == 'no' and (strtotime($Val['Training']['date_expired']) < strtotime($currentDate)))  {
				$trainingArr[$Key]['color'] 	= '#FF0000';
			}   
			
			//1. All the training and action item/subtask not expired and not completed will be on calendar and list in blue.
			elseif ($Val['Training']['training_completed'] == 'no' and (strtotime($Val['Training']['date_expired']) > strtotime($currentDate)))  {
				$trainingArr[$Key]['color'] 	= '#3090C7';
			}              
        }		
        //end 
		
        //get action items records
		//Start
		
        $actionitems  = $this->Actionitem->find(
			'all', array (
				'conditions' => array (
					'OR' => array (
						'Actionitem.date_due <>' => NULL, 
						'Actionitem.date_due <>' => '0000-00-00'
					),
					'FIND_IN_SET(\''. $loggedinUserid .'\',Actionitem.employee_responsible_id)'
				), 
				'contain' =>array(
					'ActionitemCategory'
				)
			)
		);
		//echo "<pre>"; print_r ($actionitems);die;       
        foreach ($actionitems as $actionitem) {
            $dataArr 			= array();
            $dataArr['title'] 	= $actionitem['ActionitemCategory']['name'];
            $dataArr['start'] 	= $actionitem['Actionitem']['date_due'];
			
			$date1 				= date_create ($currentDate);
			$date2 				= date_create ($actionitem['Actionitem']['date_due']);
			if ($date2 > $date1)  {
				$diff					= date_diff($date2,$date1);
				$datediff			= $diff->format("%a");
				//echo $diff->format("%R%a");die;
			} 
			
			//echo $datediff;
			
            $dataArr['color']	= '#3090C7';
            if ((strtotime($dataArr['start']) < strtotime($currentDate))) {
                $dataArr['color'] = '#FF0000';
            }
			
			if (isset($datediff))  {
				if ($datediff < 30)  {
					$actionitem['ActionitemCategory']['name'].' '.$actionitem['Actionitem']['date_completed'];
					$dataArr['color'] = '#257e4a';
				}				
			}
			
            if ($actionitem['Actionitem']['completed'] == 'yes')   {
				$dataArr['title'] 	= $actionitem['ActionitemCategory']['name'].' '.$actionitem['Actionitem']['date_completed'];
                $dataArr['color'] = '#257e4a';
            }
			
			if ($actionitem['Actionitem']['completed'] == 'yes' and (strtotime($dataArr['start']) > strtotime($currentDate)))   {
				$dataArr['title'] 	= $actionitem['ActionitemCategory']['name'];
                $dataArr['color'] = '#FF0000';
            }
            array_push($trainingArr, $dataArr);
        }

		$actionitemSubtasks	= $this->ActionitemSubtask->find (
			'all', array (
				'conditions' => array (
					'OR' => array (
						'ActionitemSubtask.subtask_date_due <>' => NULL, 
						'ActionitemSubtask.subtask_date_due <>' => '0000-00-00'
					)
				),
				'recursive' => 2
			)
		);
		//echo "<pre>"; print_r ($actionitems);die;
		
        foreach ($actionitemSubtasks as $actionitemSubtask)  {
            $dataArrSubtask = array();
            $dataArrSubtask['title'] = $actionitemSubtask['ActionitemType']['name'];
            $dataArrSubtask['start'] = $actionitemSubtask['ActionitemSubtask']['subtask_date_due'];
            $dataArrSubtask['color'] = '#3090C7';
            if ((strtotime($dataArrSubtask['start']) < strtotime($currentDate))) {

                $dataArrSubtask['color'] = '#FF0000';
            }
            if ($actionitemSubtask['ActionitemSubtask']['subtask_completed'] == 'yes') {

                $dataArrSubtask['color'] = '#257e4a';
            }
            array_push($trainingArr, $dataArrSubtask);
        }
        //end
		
        echo json_encode($trainingArr);
    }

    //function to get training data
    function trainings() {
        $this->Training->bindModel(array('belongsTo' => array(
                'Vendor' => array(
                    'foreignKey' => false,
                    'conditions' => array("Training.training_provider_id = Vendor.id")),
                'TrainingType' => array(
                    'foreignKey' => false,
                    'conditions' => array("Training.type_id = TrainingType.id")),
                'TrainingTopic' => array(
                    'foreignKey' => false,
                    'conditions' => array("Training.topics_covered_id = TrainingTopic.id")),
                'Branch' => array(
                    'foreignKey' => false,
                    'conditions' => array("Training.branch_id = Branch.id")),
                'Department' => array(
                    'foreignKey' => false,
                    'conditions' => array("User.department_id = Department.id")),
            )
                ), false);

        $conditions = array();

        //Get the User's access Branch list
        $accessBranchList = $this->_getAccessBranchListArray();
        $accessDeptList = $this->_getAccessDeptListArray();


        if ($accessBranchList != 'all') {
            $branchCndnt = array('Training.branch_id' => $accessBranchList);
            $conditions = array_merge($conditions, $branchCndnt);
        }


        if ($accessDeptList != 'all') {
            $deptsCndnt = array('Training.department_id' => $accessDeptList);
            $conditions = array_merge($conditions, $deptsCndnt);
        }

        //$conditions		=	array_merge ( $conditions , array('OR'=>array('Training.date_expired <>' =>NULL,'Training.date_expired <>' =>'0000-00-00')) );
        return $conditions;
    }

    //function to export the data for supervisor scorecard                
    function scorecardexport() {

        $this->autoRender = false;
        $this->layout = false;

        //creating letters array
        $letters = array();
        $letter = 'A';
        while ($letter !== 'AAA') {
            $letters[] = $letter++;
        }
        App::import('Vendor', 'Classes/PHPExcel');
        $workbook = new PHPExcel();
        $style = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        );
        $sheet = $workbook->getActiveSheet();
        if (!empty($this->data)) {
            //creating header for csv and excel report
            //header 1st row
            $sheet->setCellValue($letters[0] . '1', 'Employee');
            $sheet->getStyle($letters[0] . '1')->getFont()->setBold(true);

            $sheet->setCellValue($letters[1] . '1', 'Department');
            $sheet->getStyle($letters[1] . '1')->getFont()->setBold(true);

            $sheet->mergeCells($letters[2] . '1' . ':' . $letters[4] . '1');
            $sheet->setCellValue($letters[2] . '1', 'Training');
            $sheet->getStyle($letters[2] . '1')->getFont()->setBold(true);
            $sheet->setCellValue($letters[2] . '1', 'Training');

            $sheet->mergeCells($letters[5] . '1' . ':' . $letters[8] . '1');
            $sheet->getStyle($letters[5] . '1')->getFont()->setBold(true);
            $sheet->setCellValue($letters[5] . '1', 'Training Related to');
            //end
            //header 2nd row
            $sheet->setCellValue($letters[0] . '2', '');
            $sheet->getStyle($letters[1] . '2')->getFont()->setBold(true);

            $sheet->setCellValue($letters[1] . '2', '');
            $sheet->getStyle($letters[1] . '2')->getFont()->setBold(true);


            $sheet->setCellValue($letters[2] . '2', 'Incomplete');
            $sheet->getStyle($letters[2] . '2')->getFont()->setBold(true);

            $sheet->setCellValue($letters[3] . '2', 'Complete');
            $sheet->getStyle($letters[3] . '2')->getFont()->setBold(true);

            $sheet->setCellValue($letters[4] . '2', 'Expired');
            $sheet->getStyle($letters[4] . '2')->getFont()->setBold(true);

            $sheet->setCellValue($letters[5] . '2', 'Accident');
            $sheet->getStyle($letters[5] . '2')->getFont()->setBold(true);

            $sheet->setCellValue($letters[6] . '2', 'Ticket');
            $sheet->getStyle($letters[6] . '2')->getFont()->setBold(true);

            $sheet->setCellValue($letters[7] . '2', 'Warning');
            $sheet->getStyle($letters[7] . '2')->getFont()->setBold(true);

            $sheet->setCellValue($letters[8] . '2', 'Safety');
            $sheet->getStyle($letters[8] . '2')->getFont()->setBold(true);
            //end
            //getting the scorecard data
            //getting all users under this supervisor                           
            $accessBranchList = $this->_getAccessBranchListArray();
            $accessDeptList = $this->_getAccessDeptListArray();
            $conditions = array();

            //$conditions = array();
            if ($accessBranchList != 'all') {
                $branchCndnt = array('User.branch_id' => $accessBranchList);
                $conditions = array_merge($conditions, $branchCndnt);
            }

            //$conditions = array();
            if ($accessDeptList != 'all') {
                $deptsCndnt = array('User.department_id' => $accessDeptList);
                $conditions = array_merge($conditions, $deptsCndnt);
            }
            if ($this->Session->read('filterScorecard') != '') {

                if ($this->Session->read('filterScorecard.branch_list') != '') {
                    $brachCndnt = array('User.branch_id' => $this->Session->read('filterScorecard.branch_list'));
                    $conditions = array_merge($conditions, $brachCndnt);
                } else {
                    $this->Session->delete('filterData');
                }
            }
            $this->Paginator->settings = array(
                'conditions' => $conditions,
                'limit' => 10000000000,
                'order' => array(
                    'User.id' => 'desc'
                )
            );
            $userArr = $this->Paginator->paginate('User');

            if (!empty($this->data['Report']['sortingorder'])) {
                $sortBy = $this->data['Report']['sortingorder'];
                //$order  = ($this->params->named['direction']=='asc')?SORT_AS:SORT_DESC;
            }

            $userdetails = $sortByArr = array();
            $currentDate = date('Y-m-d');
            foreach ($userArr as $userKey => $userData) {
                $userdetails[$userKey]['name'] = $this->_getEmpName($userData['User']['id']);
                $userdetails[$userKey]['department'] = $this->_getDepartmentName($userData['User']['department_id']);

                //getting the complete,incomplete,expire training
                $userdetails[$userKey]['completed'] = $this->Training->find('count', array('conditions' => array('training_completed' => 'yes', 'Training.employee_id' => $userData['User']['id'])));
                $userdetails[$userKey]['incompleted'] = $this->Training->find('count', array('conditions' => array('training_completed' => 'no', 'Training.employee_id' => $userData['User']['id'])));
                $userdetails[$userKey]['expired'] = $this->Training->find('count', array('conditions' => array('date_expired <' => $currentDate, 'Training.employee_id' => $userData['User']['id'])));

                //count the tranings for accident,warning,safety,ticket
                $userdetails[$userKey]['accident'] = $this->Training->find('count', array('conditions' => array('related_to' => 'accident', 'Training.employee_id' => $userData['User']['id'])));
                $userdetails[$userKey]['warning'] = $this->Training->find('count', array('conditions' => array('related_to' => 'warning', 'Training.employee_id' => $userData['User']['id'])));
                $userdetails[$userKey]['safety'] = $this->Training->find('count', array('conditions' => array('related_to' => 'safety', 'Training.employee_id' => $userData['User']['id'])));
                $userdetails[$userKey]['ticket'] = $this->Training->find('count', array('conditions' => array('related_to' => 'ticket', 'Training.employee_id' => $userData['User']['id'])));
            }




            //sorting the array
            if (!empty($this->data['Report']['sortby'])) {
                foreach ($userdetails as $key => $data) {
                    $sortByArr[$key] = $data[$sortBy];
                }

                if (!empty($this->data['Report']['sortby'])) {

                    array_multisort($sortByArr, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $userdetails);
                } else {
                    array_multisort($sortByArr, SORT_DESC, SORT_NATURAL | SORT_FLAG_CASE, $userdetails);
                }
            }

            //getting the records
            $dataRow = 3;
            foreach ($userdetails as $newrecordkey => $newrecordVal) {
                $countColumns = 0;
                foreach ($newrecordVal as $key => $value) {
                    $sheet->setCellValue($letters[$countColumns] . $dataRow, $value);
                    $sheet->getStyle($letters[$countColumns] . $dataRow);
                    $countColumns++;
                }
                $dataRow++;
            }
            //end processing the records



            if (!isset($this->data['email'])) {
                $email = '';
            } else {
                $email = $this->data['Report']['emailuser'];
            }
            //end header


            $filename = 'Scorecard Report-' . time() . '.' . $this->data['Report']['file_type'];
            //call function to save or out the csv or excel file

            $this->outputReport($this->data, $filename, $workbook);
        }
    }

    //function to save or output th report in csv of excel format
    function outputReport($reportArr, $filename, $workbook) {

        $filepath = dirname(APP) . '/app/webroot/export/' . $filename;

        //checking file type (csv or excel)
        if ($reportArr['Report']['file_type'] == 'csv') {
            //code to generate a csv file
            $writer = new PHPExcel_Writer_CSV($workbook);
            $writer->setDelimiter(';');
            $writer->setEnclosure('');
            $writer->setLineEnding("\r\n");
            $writer->setSheetIndex(0);
        } elseif ($reportArr['Report']['file_type'] == 'pdf') {
            $this->scorecardPdf($filepath);
        } else {
            //code to generate a excel file 
            $writer = new PHPExcel_Writer_Excel5($workbook);
        }



        //sending the attachment
        if (isset($reportArr['email'])) {
            if ($reportArr['Report']['file_type'] != 'pdf') {
                $writer->save($filepath);
            }
            //getting the full name of loggedin user
            $userFullName = $this->_getEmpName($this->Session->read('user_details.id'));
            $to = $reportArr['Report']['emailuser'];
            $content = 'Hi,<br/>' . $userFullName . ' has emailed you the attached file from mySOAR Solutions. ';
            $attachments = WWW_ROOT . 'export/' . $filename;
            $this->sendMailwithAttachments('Exported Report Data', $content, $to, $attachments);
            //getting unlink or remove file from server
            unlink($attachments);
            $this->Session->setFlash(__("Exported report has been sent to email successfully."));
            $this->redirect($reportArr['Report']['pageurl']);
            exit();
        } else {
            if ($reportArr['Report']['file_type'] == 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
            } elseif ($reportArr['Report']['file_type'] == 'pdf') {
                
            } else {
                header('Content-type: application/vnd.ms-excel');
                $writer->save('php://output');
            }
        }
    }

    //get training scorecard User's listing condition array
    function scorecardUserCond() {
        //getting all users under this supervisor                           
        $accessBranchList = $this->_getAccessBranchListArray();
        $accessDeptList = $this->_getAccessDeptListArray();
        $conditions = array();

        //$conditions = array();
        if ($accessBranchList != 'all') {
            $branchCndnt = array('User.branch_id' => $accessBranchList);
            $conditions = array_merge($conditions, $branchCndnt);
        }

        //$conditions = array();
        if ($accessDeptList != 'all') {
            $deptsCndnt = array('User.department_id' => $accessDeptList);
            $conditions = array_merge($conditions, $deptsCndnt);
        }
        if ($this->Session->read('filterScorecard') != '') {

            if ($this->Session->read('filterScorecard.branch_list') != '') {
                $brachCndnt = array('User.branch_id' => $this->Session->read('filterScorecard.branch_list'));
                $conditions = array_merge($conditions, $brachCndnt);
            } else {
                $this->Session->delete('filterData');
            }
        }
        return $conditions;
    }

    //function to generate pdf 
    //function to generate the pdf listing of scorecard
    function scorecardPdf($filepath = '') {
        ob_start();
        $this->layout = 'pdf';
        $this->autoRender = false;
        App::import('Vendor', 'xtcpdf');
        global $tcpdf;
        global $html;
        $tcpdf = new XTCPDF();
        $textfont = 'freesans'; // looks better, finer, and more condensed than 'dejavusans'

        $tcpdf->SetAuthor("MySoarSolutions");

        $tcpdf->SetAutoPageBreak(true, 20);
        $tcpdf->setHeaderFont(array($textfont, '', 15));

        // add a page (required with recent versions of tcpdf)
        $tcpdf->AddPage('L');

        $html = '';
        $html .= '<table><tr>
			<th width="100%" style="padding-bottom:30px;">
			<img src="http://mysoarsolutions.com/bosslubricants/img/images/company-logo.png" alt="" style="width:120px;">
			<br>
			</th></tr></table>';

        //getting the scorecard data
        $conditions = $this->scorecardUserCond();
        $this->Paginator->settings = array(
            'conditions' => $conditions,
            'limit' => 10000000000,
            'order' => array(
                'User.id' => 'desc'
            )
        );
        $userArr = $this->Paginator->paginate('User');

        if (!empty($this->params->named)) {
            $sortBy = $this->params->named['sort'];
            //$order  = ($this->params->named['direction']=='asc')?SORT_AS:SORT_DESC;
        }

        $userdetails = $sortByArr = array();
        $currentDate = date('Y-m-d');
        foreach ($userArr as $userKey => $userData) {
            $userdetails[$userKey]['name'] = $this->_getEmpName($userData['User']['id']);
            $userdetails[$userKey]['department'] = $this->_getDepartmentName($userData['User']['department_id']);

            //getting the complete,incomplete,expire training
            $userdetails[$userKey]['completed'] = $this->Training->find('count', array('conditions' => array('training_completed' => 'yes', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['incompleted'] = $this->Training->find('count', array('conditions' => array('training_completed' => 'no', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['expired'] = $this->Training->find('count', array('conditions' => array('date_expired <' => $currentDate, 'Training.employee_id' => $userData['User']['id'])));

            //count the tranings for accident,warning,safety,ticket
            $userdetails[$userKey]['accident'] = $this->Training->find('count', array('conditions' => array('related_to' => 'accident', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['warning'] = $this->Training->find('count', array('conditions' => array('related_to' => 'warning', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['safety'] = $this->Training->find('count', array('conditions' => array('related_to' => 'safety', 'Training.employee_id' => $userData['User']['id'])));
            $userdetails[$userKey]['ticket'] = $this->Training->find('count', array('conditions' => array('related_to' => 'ticket', 'Training.employee_id' => $userData['User']['id'])));
        }




        //sorting the array
        if (!empty($this->params->named)) {
            foreach ($userdetails as $key => $data) {
                $sortByArr[$key] = $data[$sortBy];
            }

            if ($this->params->named['direction'] == 'asc') {

                array_multisort($sortByArr, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $userdetails);
            } else {
                array_multisort($sortByArr, SORT_DESC, SORT_NATURAL | SORT_FLAG_CASE, $userdetails);
            }
        }
        $html .= '<table>
			<tr>
			<th width="100%" style="font-weight:bold;font-size:12px;padding-bottom:10px;">
				Training Scorecard
			</th>						
		</tr></table>
		<br>
		<table>
			<tr>
			<th  style="font-weight:bold;font-size:11px;">
				Employee
			</th>
			<th style="font-weight:bold;font-size:11px;">
				Department
			</th>
			<th colspan="3" style="font-weight:bold;font-size:11px;">
				Training
			</th>
			<th  colspan="4" style="font-weight:bold;font-size:11px;">
				Training Related to 
			</th>
							
                        </tr>
                        
                        <tr>
			<th  style="font-weight:bold;font-size:11px;">
				
			</th>
			<th style="font-weight:bold;font-size:11px;">
				
			</th>
			<th style="font-weight:bold;font-size:11px;">
				Incomplete
			</th>
			<th  style="font-weight:bold;font-size:11px;">
				Complete
			</th>
                        <th  style="font-weight:bold;font-size:11px;">
				Expired
			</th>
                        <th  style="font-weight:bold;font-size:11px;">
				Accident
			</th>
                        <th  style="font-weight:bold;font-size:11px;">
				Ticket
			</th>
                        <th  style="font-weight:bold;font-size:11px;">
				Warning
			</th>
                        <th  style="font-weight:bold;font-size:11px;">
				Safety
			</th>
							
                        </tr>
                        ';

        foreach ($userdetails as $content) {

            $html .= '<tr>
                                    <td style="font-size:10px;">' . $content["name"] . '</td>
                                    <td style="font-size:10px;">' . $content["department"] . '</td>
                                    <td style="font-size:10px;">' . $content["completed"] . '</td>
                                    <td style="font-size:10px;">' . $content["incompleted"] . '</td>                                    
                                    <td style="font-size:10px;">' . $content["expired"] . '</td>	
                                    <td style="font-size:10px;">' . $content["accident"] . '</td>
                                    <td style="font-size:10px;">' . $content["warning"] . '</td>
                                    <td style="font-size:10px;">' . $content["safety"] . '</td>                                    
                                    <td style="font-size:10px;">' . $content["ticket"] . '</td>
                                </tr>';
        }
        $html .= '</table>';
        ob_end_clean();
        $tcpdf->writeHTML($html, true, false, true, false, '');
        if ($filepath == '') {
            $filename = "Training_Scorecard_" . date("Y-m-d") . ".pdf";
            echo $tcpdf->Output($filename, 'D');
        } else {
            echo $tcpdf->Output($filepath, 'F');
        }
    }

    //here map api's
    function vendors() {
        $this->layout = false;
        $this->autoRender = false;
        $listingVendors = $this->listingVendors();
        echo json_encode($listingVendors);
    }

    //listing vendors with location lat long
    function vendorsWithLocation() {
        $this->layout = false;
        $this->autoRender = false;
        $listingVendors = $this->listingVendors();
        $vendorWithLoc = array();
        foreach ($listingVendors as $key => $vendor) {

            $vendorArr = $this->Vendor->find('first', array('conditions' => array('id' => $key)));
            $locationAddress = '';
            $locationName = '';
            if (!empty($vendorArr)) {
                $mapLoc = '';
                //if branch name is not empty
                if (!empty($vendorArr['Vendor']['vendor_name'])) {
                    $mapLoc.='<b>' . $vendorArr['Vendor']['vendor_name'] . '</b><br/>';
                }

                //get lat lng
                //province
                $province = !empty($vendorArr['Vendor']['state_id']) ? $this->_getProvinceName($vendorArr['Vendor']['state_id']) : '';
                //country
                $country = !empty($vendorArr['Vendor']['country_id']) ? $this->_getCountryName($vendorArr['Vendor']['country_id']) : '';

                if (!empty($vendorArr['Vendor']['mail_address'])) {
                    $locationAddress = $vendorArr['Vendor']['mail_address'];
                } elseif (!empty($vendorArr['Vendor']['mail_address2'])) {
                    $locationAddress = $vendorArr['Vendor']['mail_address2'];
                }
                if (!empty($locationAddress)) {
                    $mapLoc.=$locationAddress . '<br/>';
                }
                //if city is not empty
                if (!empty($vendorArr['Vendor']['city'])) {
                    $locationAddress = !empty($locationAddress) ? $locationAddress . ',' . $vendorArr['Vendor']['city'] : $vendorArr['Vendor']['city'];
                    $mapLoc = !empty($mapLoc) ? $mapLoc . ' ' . $vendorArr['Vendor']['city'] : $vendorArr['Vendor']['city'];
                }

                $locationAddress = !empty($province) ? $locationAddress . ',' . $province : $locationAddress;
                $locationAddress = !empty($country) ? $locationAddress . ',' . $country : $locationAddress;
                $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $province : $province;
                $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $country : $country;

                //if postal code is not empty
                if (!empty($vendorArr['Vendor']['postal_code'])) {
                    $locationAddress = !empty($locationAddress) ? $locationAddress . ',' . $vendorArr['Vendor']['postal_code'] : $vendorArr['Vendor']['postal_code'];
                    $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $vendorArr['Vendor']['postal_code'] : $vendorArr['Vendor']['postal_code'];
                }
                //if phone is not empty
                if (!empty($vendorArr['Vendor']['contact_phoneno'])) {

                    $mapLoc = !empty($mapLoc) ? $mapLoc . '<br/>' . $vendorArr['Vendor']['contact_phoneno'] : $vendorArr['Vendor']['contact_phoneno'];
                }
                $mapLoc = !empty($mapLoc) ? $mapLoc . '<br/>' . 'Vendor' : 'Vendor';
                $latitude = $longitude = '';
                if (!empty($locationAddress)) {
                    // We get the JSON results from this request
                    $geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($locationAddress) . '&sensor=false');
                    // We convert the JSON to an array
                    $geo = json_decode($geo, true);
                    // If everything is cool
                    if ($geo['status'] = 'OK' && !empty($geo['results'])) {

                        // We set our values
                        $latitude = @$geo['results'][0]['geometry']['location']['lat'];
                        $longitude = @$geo['results'][0]['geometry']['location']['lng'];
                    }
                }
                if (!empty($latitude) && !empty($longitude)) {
                    $dataArr['Lat'] = $latitude;
                    $dataArr['Lng'] = $longitude;
                    $dataArr['city'] = $mapLoc;
                    $vendorWithLoc[] = $dataArr;
                }
            }
        }
        echo json_encode($vendorWithLoc);
    }

    function vehicles() {
        $this->layout = false;
        $this->autoRender = false;
        $listingVendors = $this->_getVehicleList();
        echo json_encode($listingVendors);
    }

    function customers() {
        $this->layout = false;
        $this->autoRender = false;
        $listingVendors = $this->_getCustomerList();
        echo json_encode($listingVendors);
    }

    //listing vendors with location lat long
    function customersWithLocation() {
        $this->layout = false;
        $this->autoRender = false;
        $listingCustomers = $this->_getCustomerList();
        $customerWithLoc = array();
        foreach ($listingCustomers as $key => $customer) {

            $customerArr = $this->Customer->find('first', array('conditions' => array('id' => $key)));
            $locationAddress = '';
            $locationName = '';
            if (!empty($customerArr)) {
                $mapLoc = '';
                //if branch name is not empty
                if (!empty($customerArr['Customer']['company_name'])) {
                    $mapLoc.='<b>' . $customerArr['Customer']['company_name'] . '</b><br/>';
                }

                //get lat lng
                //province
                $province = !empty($customerArr['Customer']['province_id']) ? $this->_getProvinceName($customerArr['Customer']['province_id']) : '';
                //country
                $country = !empty($customerArr['Customer']['country_id']) ? $this->_getCountryName($customerArr['Customer']['country_id']) : '';

                if (!empty($customerArr['Customer']['mail_address'])) {
                    $locationAddress = $customerArr['Customer']['mail_address'];
                } elseif (!empty($customerArr['Customer']['mail_address_second'])) {
                    $locationAddress = $customerArr['Customer']['mail_address_second'];
                }
                if (!empty($locationAddress)) {
                    $mapLoc.=$locationAddress . '<br/>';
                }

                //if city is not empty
                if (!empty($customerArr['Customer']['city'])) {
                    $locationAddress = !empty($locationAddress) ? $locationAddress . ',' . $customerArr['Customer']['city'] : $customerArr['Customer']['city'];
                    $mapLoc = !empty($mapLoc) ? $mapLoc . ' ' . $customerArr['Customer']['city'] : $customerArr['Customer']['city'];
                }

                $locationAddress = !empty($province) ? $locationAddress . ',' . $province : $locationAddress;
                $locationAddress = !empty($country) ? $locationAddress . ',' . $country : $locationAddress;
                $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $province : $province;
                $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $country : $country;

                //if postal code is not empty
                if (!empty($customerArr['Customer']['postal_code'])) {
                    $locationAddress = !empty($locationAddress) ? $locationAddress . ',' . $customerArr['Customer']['postal_code'] : $customerArr['Customer']['postal_code'];
                    $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $customerArr['Customer']['postal_code'] : $customerArr['Customer']['postal_code'];
                }
                //if phone is not empty
                if (!empty($customerArr['Customer']['phone_number'])) {

                    $mapLoc = !empty($mapLoc) ? $mapLoc . '<br/>' . $customerArr['Customer']['phone_number'] : $customerArr['Customer']['phone_number'];
                }
                $mapLoc = !empty($mapLoc) ? $mapLoc . '<br/>' . 'Customer' : 'Customer';

                $latitude = $longitude = '';
                if (!empty($locationAddress)) {
                    // We get the JSON results from this request
                    $geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($locationAddress) . '&sensor=false');
                    // We convert the JSON to an array
                    $geo = json_decode($geo, true);
                    // If everything is cool
                    if ($geo['status'] = 'OK' && !empty($geo['results'])) {

                        // We set our values
                        $latitude = @$geo['results'][0]['geometry']['location']['lat'];
                        $longitude = @$geo['results'][0]['geometry']['location']['lng'];
                    }
                }



                if (!empty($latitude) && !empty($longitude)) {
                    $dataArr['Lat'] = $latitude;
                    $dataArr['Lng'] = $longitude;
                    $dataArr['city'] = $mapLoc;
                    $branchWithLoc[] = $dataArr;
                }
            }
        }
        echo json_encode($branchWithLoc);
    }

    function branches() {
        $this->layout = false;
        $this->autoRender = false;
        $listingVendors = $this->_getBranchesList();
        echo json_encode($listingVendors);
    }

    //listing vendors with location lat long
    function branchesWithLocation() {
        $this->layout = false;
        $this->autoRender = false;
        $listingBranches = $this->_getBranchesList();
        $branchWithLoc = array();
        foreach ($listingBranches as $key => $branch) {

            $branchArr = $this->Branch->find('first', array('conditions' => array('id' => $key)));
            $locationAddress = '';
            $locationName = '';
            if (!empty($branchArr)) {
                $mapLoc = '';
                //if branch name is not empty
                if (!empty($branchArr['Branch']['branch_name'])) {
                    $mapLoc.='<b>' . $branchArr['Branch']['branch_name'] . '</b><br/>';
                }

                //get lat lng
                //province
                $province = !empty($branchArr['Branch']['province_id']) ? $this->_getProvinceName($branchArr['Branch']['province_id']) : '';
                //country
                $country = !empty($branchArr['Branch']['country_id']) ? $this->_getCountryName($branchArr['Branch']['country_id']) : '';

                if (!empty($branchArr['Branch']['branch_address'])) {
                    $locationAddress = $branchArr['Branch']['branch_address'];
                } elseif (!empty($branchArr['Branch']['branch_address2'])) {
                    $locationAddress = $branchArr['Branch']['branch_address2'];
                }

                if (!empty($locationAddress)) {
                    $mapLoc.=$locationAddress . '<br/>';
                }

                //if city is not empty
                if (!empty($branchArr['Branch']['city'])) {
                    $locationAddress = !empty($locationAddress) ? $locationAddress . ',' . $branchArr['Branch']['city'] : $branchArr['Branch']['city'];
                    $mapLoc = !empty($mapLoc) ? $mapLoc . ' ' . $branchArr['Branch']['city'] : $branchArr['Branch']['city'];
                }

                $locationAddress = !empty($province) ? $locationAddress . ',' . $province : $locationAddress;
                $locationAddress = !empty($country) ? $locationAddress . ',' . $country : $locationAddress;
                $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $province : $province;
                $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $country : $country;

                //if postal code is not empty
                if (!empty($branchArr['Branch']['branch_postalcode'])) {
                    $locationAddress = !empty($locationAddress) ? $locationAddress . ',' . $branchArr['Branch']['branch_postalcode'] : $branchArr['Branch']['branch_postalcode'];
                    $mapLoc = !empty($mapLoc) ? $mapLoc . ', ' . $branchArr['Branch']['branch_postalcode'] : $branchArr['Branch']['branch_postalcode'];
                }
                //if phone is not empty
                if (!empty($branchArr['Branch']['branch_phone'])) {

                    $mapLoc = !empty($mapLoc) ? $mapLoc . '<br/>' . $branchArr['Branch']['branch_phone'] : $branchArr['Branch']['branch_phone'];
                }
                $mapLoc = !empty($mapLoc) ? $mapLoc . '<br/>' . 'Branch' : 'Branch';

                $latitude = $longitude = '';
                if (!empty($locationAddress)) {
                    // We get the JSON results from this request
                    $geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($locationAddress) . '&sensor=false');
                    // We convert the JSON to an array
                    $geo = json_decode($geo, true);
                    // If everything is cool
                    if ($geo['status'] = 'OK' && !empty($geo['results'])) {

                        // We set our values
                        $latitude = @$geo['results'][0]['geometry']['location']['lat'];
                        $longitude = @$geo['results'][0]['geometry']['location']['lng'];
                    }
                }



                if (!empty($latitude) && !empty($longitude)) {
                    $dataArr['Lat'] = $latitude;
                    $dataArr['Lng'] = $longitude;
                    $dataArr['city'] = $mapLoc;
                    $branchWithLoc[] = $dataArr;
                }
            }
        }
        echo json_encode($branchWithLoc);
    }

    //ge the company lat lng
    function companyAddress() {
        $this->layout = false;
        $this->autoRender = false;
        //get the Company details
        $companyDetails = $this->Company->find('first');
        if (!empty($companyDetails['Company']['mail_address'])) {
            $locationAddress = $companyDetails['Company']['mail_address'];
        } elseif (!empty($companyDetails['Company']['mail_address2'])) {
            $locationAddress = $companyDetails['Company']['mail_address2'];
        }
        //province
        $province = !empty($companyDetails['Company']['province_id']) ? $this->_getProvinceName($companyDetails['Company']['province_id']) : '';
        //country
        $country = !empty($companyDetails['Company']['country_id']) ? $this->_getCountryName($companyDetails['Company']['country_id']) : '';


        //if city is not empty
        if (!empty($companyDetails['Company']['city'])) {
            $locationAddress = !empty($locationAddress) ? $locationAddress . ',' . $companyDetails['Company']['city'] : $companyDetails['Company']['city'];
        }

        $locationAddress = !empty($province) ? $locationAddress . ',' . $province : $locationAddress;
        $locationAddress = !empty($country) ? $locationAddress . ',' . $country : $locationAddress;


        //if postal code is not empty
        if (!empty($companyDetails['Company']['postal_code'])) {
            $locationAddress = !empty($locationAddress) ? $locationAddress . ',' . $companyDetails['Company']['postal_code'] : $companyDetails['Company']['postal_code'];
        }

        $latitude = $longitude = '';
        if (!empty($locationAddress)) {
            // We get the JSON results from this request
            $geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($locationAddress) . '&sensor=false');
            // We convert the JSON to an array
            $geo = json_decode($geo, true);

            // If everything is cool
            if ($geo['status'] = 'OK' && !empty($geo['results'])) {

                // We set our values
                $latitude = @$geo['results'][0]['geometry']['location']['lat'];
                $longitude = @$geo['results'][0]['geometry']['location']['lng'];
            }
        }
        $dataArr['Lat'] = $latitude;
        $dataArr['Lng'] = $longitude;
        echo json_encode($dataArr);
    }

    //function to genearte the e-signature
    public function esignature() {
        $loggedinUserArr = $this->Session->read();
        $user_type = $loggedinUserArr['user_type'];
        $username = '';
        $loggedinUserid = $this->Session->read('user_details.id');




        //code for posted data
        if (isset($this->data) && !empty($this->data)) {
            $username = $this->data['User']['username'];

            $fontclass = $this->data['User']['signature_font'];


            $this->User->set($this->data);
            $this->User->validate = $this->User->signatureValidate;

            if ($this->User->validates()) {
                if ($user_type == 'employeeLogin') {
                    $modelName = 'User';
                } elseif ($user_type == 'companyLogin') {
                    $modelName = 'Company';
                    $this->$modelName->setDataSource('mysoarDb');
                }

                $signatureData[$modelName]['esignature'] = $this->data['User']['username'];
                $signatureData[$modelName]['signature_id'] = $this->data['User']['signature_id'];
                $signatureData[$modelName]['id'] = $loggedinUserid;
                //pr($signatureData);die;
                if ($this->$modelName->save($signatureData)) {
                    $this->Session->setFlash(__("E-signature has been updated"));
                    return $this->redirect(array('controller' => 'users', 'action' => 'esignature'));
                }
            } else {
                // invalid
                $errors = $this->User->validationErrors;
            }
        } else {//die('else');
            //get loggedin user data
            if ($user_type == 'employeeLogin') {
                $modelName = 'User';
            } elseif ($user_type == 'companyLogin') {
                $modelName = 'Company';
                $this->$modelName->setDataSource('mysoarDb');
            }
            $empData = $this->$modelName->find('first', array('conditions' => array($modelName . '.id' => $loggedinUserid)));

            if (!empty($empData) && !empty($empData[$modelName]['esignature'])) {
                $username = $empData[$modelName]['esignature'];
                $fontclass = $empData[$modelName]['signature_id'];
            } else {
                if ($loggedinUserArr['user_type'] == 'employeeLogin') {
                    $username = $loggedinUserArr['user_details']['user_fname'] . ' ' . $loggedinUserArr['user_details']['user_lname'];
                } elseif ($loggedinUserArr['user_type'] == 'companyLogin') {
                    $username = $loggedinUserArr['user_details']['first_name'] . ' ' . $loggedinUserArr['user_details']['last_name'];
                }
                $fontclass = 'font2';
            }
        }
        //end

        $this->set('signature', $this->_addedSignatures());
        $this->set('fontclass', $fontclass);
        $this->set('username', $username);
    }

    /*
     * function is use for update employee count in jobfunctions table
     *  and count userfunction_id in user table
     */

    public function updateJobFunction($jobFunctionId) {
		if (!empty($jobFunctionId)) {
			foreach ($jobFunctionId as $functionId) {
				$conditions = array('User.userjobfuntion_id like' => '%-' . $functionId . '-%');
				$countActiveUsers = $this->User->find('count', array('conditions' => $conditions));
				$this->JobFunction->id = $functionId;
				$this->JobFunction->saveField('employees_count', $countActiveUsers);
			}
		}
    }

    /*
     * function is use for reduce 1 count
     *  from employee_count in jobfunctions table

     */

    public function reduceEmployeeCount($oldUserData) {
        //  pr($oldUserData);
        $new_str = str_replace("-", '', $oldUserData['User']['userjobfuntion_id']);
        $jobFunctionId = explode(',', $new_str);
        foreach ($jobFunctionId as $functionId) {

           $this->JobFunction->id = $functionId;
            $this->JobFunction->updateAll(array(
               'JobFunction.employees_count' => 'JobFunction.employees_count - 1'), array('JobFunction.id' => $functionId));

//            $this->JobFunction->id = $functionId;
//            $this->JobFunction->saveField(array(
//                'JobFunction.employees_count' => 'JobFunction.employees_count - 1'));
        }
    }
	
	public function get_training ()  {
		//echo "<pre>";print_r($_POST);
		$this->layout = false;
		$get_all_job_function	=	array();
		if($_POST)  {
			$get_all_job_function = $this->JobFunctionTraining->find(
				'all',array(
					'conditions'	=> array(
						'JobFunctionTraining.job_function_id'=>$_POST['content'],
						'JobFunctionTraining.create_record'	=>1,
						'JobFunctionTraining.is_delete'	=>'no',
						'TrainingType.id <>'=>Null
					),
					'contain'=>array(
						'JobFunction'	=> array ('fields'=>array('id','job_function')),
						'TrainingType'	=> array ('fields'=>array('id','name'))
					),
					'order'	=> array ('TrainingType.name'=>'ASC')
				)
			);
		}
		
		//echo "<pre>";print_r($get_all_job_function);die;
		$this->set('data',$get_all_job_function);
		 $this->render();
		//pr($get_all_job_function);die;
	}
	
	public function get_training_edit ()  {
		//Configure::write('debug', 2);
		$this->layout = false;		
		$trainging_type				= array ();		
		$get_all_job_function	=	array();		
		$i				= 0;
		$index		= 1;				
		$newArr 	= array();
		$newArr1 	= array();
		$newArr2 	= array();
		$newarr3	= array();		
		$repeat_assoc_training_ids	=	array();		
		$repeat_assoc_training_job_function_ids	=	array();		
		
		if(!empty($_POST))  {
			
			/* Get All trainings of Job Functions */			
			
			$get_all_job_function = $this->JobFunctionTraining->find(
				'all',array(
					'conditions'	=> array(
						'JobFunctionTraining.job_function_id'=>$_POST['content'],
						'JobFunctionTraining.create_record'	=>1,
						//'JobFunctionTraining.is_delete'	=>'no',
						'TrainingType.id <>'=>Null
					),
					'contain'=>array(
						'JobFunction'	=> array ('fields'=>array('id','job_function')),
						'TrainingType'	=> array ('fields'=>array('id','name'))
					), 
					'order'=>array('TrainingType.name'=>'ASC'), 
				)
			);
			
			$repeat_assoc_training = $this->Training->find(
				'all',array(
					'conditions'	=> array(
						'Training.employee_id'	=> $_POST['employee_id'],
						'Training.is_assoc'			=> 'yes',
						'Training.repeat_assoc'	=> 'yes'
					),
					'fields' => array('id','type_id','job_function_id','is_assoc','repeat_assoc'),
					'contain' => array()
				)
			);			
			
			foreach ($repeat_assoc_training as $ratid)  {
				array_push($repeat_assoc_training_ids,$ratid['Training']['id']);
			}
			
			//echo "<pre>";print_r ($get_all_job_function);
			//echo "<pre>";print_r ($repeat_assoc_training_ids);
			//die;
					
			foreach ($get_all_job_function as $info)  {
				
				$get_user_training	 = $this->Training->find(
					'first',array(
						'conditions'=>array(
							'Training.employee_id'=>$_POST['employee_id'],
							'Training.type_id'		=>$info['TrainingType']['id'],
							//'Training.training_completed'		=> 'no'
							//'Training.date_expire_status'		=> 'no',
							'Training.repeat_assoc <>'		=> 'yes',
							//'Training.job_function_id <>'		=> 0,
						),
						'contain'=>array()
					)
				);			
				
							
				//echo "<pre>"; print_r ($get_user_training);die;
				
				//echo $info['TrainingType']['name'];
				if (!in_array($get_user_training['Training']['id'],$repeat_assoc_training_ids))  {
					//echo "<pre>";print_r ($get_user_training);die;
					if ($info['JobFunctionTraining']['is_delete'] == 'no' or $info['JobFunctionTraining']['is_delete'] == Null)  {		
						
						if (!in_array($info['TrainingType']['id'],$trainging_type))  {
							array_push($trainging_type,$info['TrainingType']['id']);						
							$final_array[$i]['TrainingType']['id'] 						= $info['TrainingType']['id'];			
							$final_array[$i]['TrainingType']['name'] 				= $info['TrainingType']['name'];
							$final_array[$i]['JobFunction']['job_function'] 		= $info['JobFunction']['job_function'];
							$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $info['JobFunctionTraining']['required_optional'];
							$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= $info['JobFunctionTraining']['job_function_id'];
							$final_array[$i]['JobFunctionTraining']['is_delete'] 					= $info['JobFunctionTraining']['is_delete'];			
							$final_array[$i]['Training']['record_created_by']  						= 'fresh';		
							
							if (!empty($get_user_training))  {
								$final_array[$i]['Training']['index'] 					= 0;
								$final_array[$i]['Training']['id'] 						= $get_user_training['Training']['id'];
								$final_array[$i]['Training']['is_assoc'] 			= $get_user_training['Training']['is_assoc'];
								$final_array[$i]['Training']['completed'] 			= $get_user_training['Training']['training_completed'];
								$final_array[$i]['Training']['date_started'] 		= $get_user_training['Training']['date_started'];
								$final_array[$i]['Training']['date_completed'] 	= !empty($get_user_training['Training']['date_completed']) ? $get_user_training['Training']['date_completed'] : Null;
								$final_array[$i]['Training']['date_expired'] 		= !empty($get_user_training['Training']['date_expired']) ? $get_user_training['Training']['date_expired'] : Null;
							}  else {
								$final_array[$i]['Training']['index'] 					= 0;
								$final_array[$i]['Training']['id'] 						= '';
								$final_array[$i]['Training']['is_assoc'] 			= '';
								$final_array[$i]['Training']['completed'] 			= '';
								$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
								$final_array[$i]['Training']['date_completed'] 	= Null;
								$final_array[$i]['Training']['date_expired'] 		= Null;
							}	
							//echo "<pre>"; print_r($final_array);die;
							if($final_array[$i]['Training']['completed'] == 'yes')  {
								//$newArr['c'][$i] = $final_array[$i];
							} elseif ($final_array[$i]['Training']['completed'] == 'no' and $final_array[$i]['Training']['is_assoc']	!= 'yes' and $final_array[$i]['Training']['repeat_assoc'] != 'yes') {
								//$newArr1['b'][$i] = $final_array[$i];
								//$i = $i + 1;			
							}  elseif ($final_array[$i]['Training']['is_assoc']	== 'yes') {
								
								$newArr2['a'][$i] = $final_array[$i];
								
								$get_jobfunctions = $this->JobFunctionTraining->find(
									'all',array(
										'conditions' => array (
											'JobFunctionTraining.training_type_id' =>$info['TrainingType']['id'],
											'JobFunctionTraining.job_function_id'  =>$_POST['content'],
											//'JobFunctionTraining.is_delete'=>'no',
											'JobFunction.id <>' =>$info['JobFunction']['id'],	
											'JobFunctionTraining.create_record'	=>1,
											'TrainingType.id <>'=>Null
										),
										'group' 	  => array('JobFunctionTraining.job_function_id'),
										'contain'   => array (
											'JobFunction'  => array (
												'fields' => array ('id','job_function')
											),
											'TrainingType'  => array (
												'fields' => array ('id','name')
											),
										)
									)
								);
								
								//echo "<pre>"; print_r ($get_jobfunctions);die; 
								if (!empty($get_jobfunctions))  {	
									$i = $i + 1;
									foreach ($get_jobfunctions as $get_jobfunction_info1)  {
										if ($get_jobfunction_info1['JobFunctionTraining']['is_delete'] == 'no' or $get_jobfunction_info1['JobFunctionTraining']['is_delete'] == Null)  {	
											$final_array[$i]['TrainingType']['id'] 		= $get_jobfunction_info1['TrainingType']['id'];				
											$final_array[$i]['TrainingType']['name'] 	= $get_jobfunction_info1['TrainingType']['name'];
											$final_array[$i]['JobFunction']['job_function'] 		= $get_jobfunction_info1['JobFunction']['job_function'];
											$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $get_jobfunction_info1['JobFunctionTraining']['required_optional'];
											$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= '';
											$final_array[$i]['JobFunctionTraining']['is_delete'] 				= '';			
											$final_array[$i]['Training']['record_created_by']  = 'fresh';		
											$final_array[$i]['Training']['disass_repeat']  		= 'fresh';		
											
											$final_array[$i]['Training']['index'] 					= 0;
											$final_array[$i]['Training']['id'] 						= '';
											$final_array[$i]['Training']['completed'] 			= '';
											$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
											$final_array[$i]['Training']['date_completed'] 	= Null;
											$final_array[$i]['Training']['date_expired'] 		= Null;
											$newArr2['a'][$i] = $final_array[$i];
											$i = $i + 1;	
										}								
									}
								}
								$i = $i + 1;		
							}	else {
								$newArr2['a'][$i] = $final_array[$i];
								
								$get_jobfunctions = $this->JobFunctionTraining->find(
									'all',array(
										'conditions' => array (
											'JobFunctionTraining.training_type_id' =>$info['TrainingType']['id'],
											'JobFunctionTraining.job_function_id'  =>$_POST['content'],
											//'JobFunctionTraining.is_delete'=>'no',
											'JobFunction.id <>' =>$info['JobFunction']['id'],	
											'JobFunctionTraining.create_record'	=>1,
											'TrainingType.id <>'=>Null
										),
										'group' 	  => array('JobFunctionTraining.job_function_id'),
										'contain'   => array (
											'JobFunction'  => array (
												'fields' => array ('id','job_function')
											),
											'TrainingType'  => array (
												'fields' => array ('id','name')
											),
										)
									)
								);
								
								//echo "<pre>"; print_r ($get_jobfunctions);die; 
								if (!empty($get_jobfunctions))  {	
									$i = $i + 1;
									foreach ($get_jobfunctions as $get_jobfunction_info1)  {
										if ($get_jobfunction_info1['JobFunctionTraining']['is_delete'] == 'no' or $get_jobfunction_info1['JobFunctionTraining']['is_delete'] == Null)  {	
											$final_array[$i]['TrainingType']['id'] 		= $get_jobfunction_info1['TrainingType']['id'];				
											$final_array[$i]['TrainingType']['name'] 	= $get_jobfunction_info1['TrainingType']['name'];
											$final_array[$i]['JobFunction']['job_function'] 		= $get_jobfunction_info1['JobFunction']['job_function'];
											$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $get_jobfunction_info1['JobFunctionTraining']['required_optional'];
											$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= '';
											$final_array[$i]['JobFunctionTraining']['is_delete'] 				= '';			
											$final_array[$i]['Training']['record_created_by']  = 'fresh';		
											
											$final_array[$i]['Training']['index'] 					= 0;
											$final_array[$i]['Training']['id'] 						= '';
											$final_array[$i]['Training']['completed'] 			= '';
											$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
											$final_array[$i]['Training']['date_completed'] 	= Null;
											$final_array[$i]['Training']['date_expired'] 		= Null;
											$newArr2['a'][$i] = $final_array[$i];
											$i = $i + 1;	
										}								
									}
								}
								$i = $i + 1;			
							}
						} 
					}  
				}
			}		
			
			$get_all_manual_trainingss	=	$this->Training->find(
				'all',array(
					'conditions'	=> array(
						'Training.job_function_id'	=> 0,
						'Training.employee_id'	=> $_POST['employee_id'],
						'TrainingType.id <>'=>Null,
						//'Training.record_created_by'	=>'expire',
						'Training.training_completed'	=>'no',
					),
					'contain'=>array(
						'JobFunction'	=> array ('fields'=>array('id','job_function')),
						'TrainingType'	=> array ('fields'=>array('id','name'))
					) 
				)
			);
			
			//echo "<pre>"; print_r($get_all_manual_trainingss);die;
			
			foreach ($get_all_manual_trainingss as $info2)  {
				array_push($trainging_type,$info2['TrainingType']['id']);
				if ($info2['Training']['additional_training_required'] == 'yes')  {
					$additional_training_required	=	0;
				} else  {
					$additional_training_required	=	0;
				}
				
				
				$final_array[$i]['TrainingType']['id'] 				= $info2['TrainingType']['id'];		
				$final_array[$i]['JobFunction']['job_function'] 	= $info2['JobFunction']['job_function'];
				$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= $info2['JobFunction']['id'];
				$final_array[$i]['TrainingType']['name'] 			= $info2['TrainingType']['name'];
				$final_array[$i]['JobFunctionTraining']['required_optional'] 		= $additional_training_required;			
				$final_array[$i]['Training']['index'] 					= 0;
				$final_array[$i]['Training']['id'] 						= $info2['Training']['id'];
				$final_array[$i]['Training']['completed'] 			= $info2['Training']['training_completed'];
				$final_array[$i]['Training']['date_started'] 		= $info2['Training']['date_started'];
				$final_array[$i]['Training']['date_completed'] 	= !empty($info2['Training']['date_completed']) ? $info2['Training']['date_completed'] : Null;
				$final_array[$i]['Training']['date_expired'] 		= !empty($info2['Training']['date_expired']) ? $info2['Training']['date_expired'] : Null;
				$final_array[$i]['Training']['record_created_by']  = 'fresh';		
				
				if($final_array[$i]['Training']['completed']=='yes') {
					$newArr['c'][$i] = $final_array[$i];
					$i = $i + 1;	 	
				} else {
						$newArr1['b'][$i] = $final_array[$i];
						$i = $i + 1;	 	
				}
				
				 
				 
				$req_job_functions = array ();
				for ($k = 0; $k <count($_POST['content']); $k++)  {
					if ($_POST['content'][$k]  != $info2['JobFunction']['id'])  {
						array_push($req_job_functions,$_POST['content'][$k]);
					}	
				}
				
				$get_jobfunction = $this->JobFunctionTraining->find(
					'all',array(
						'conditions' => array (
							'JobFunctionTraining.training_type_id'=>$info2['TrainingType']['id'],
							'JobFunctionTraining.job_function_id'=>$req_job_functions,
							'JobFunctionTraining.create_record'	=>1,
							'JobFunctionTraining.is_delete'	=>'no',
							'TrainingType.id <>'=>Null
						),
						'group' => array('JobFunctionTraining.job_function_id'),
					)
				);
				
				//echo "<pre>"; print_r ($get_jobfunction);die; 
				
				if (!empty($get_jobfunction))  {	
					if($final_array[$i-1]['Training']['completed']=='yes') {					
						foreach ($get_jobfunction as $get_jobfunction_info)  {
							$final_array[$i]['TrainingType']['id'] 		= '';			
							$final_array[$i]['TrainingType']['name'] 	= '';
							$final_array[$i]['JobFunction']['job_function'] 		= $get_jobfunction_info['JobFunction']['job_function'];
							$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $get_jobfunction_info['JobFunctionTraining']['required_optional'];
							$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= '';
							$final_array[$i]['JobFunctionTraining']['is_delete'] 				= '';			
							$final_array[$i]['Training']['record_created_by']  = 'fresh';		
							
							$final_array[$i]['Training']['index'] 					= 0;
							$final_array[$i]['Training']['id'] 						= '';
							$final_array[$i]['Training']['completed'] 			= '';
							$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
							$final_array[$i]['Training']['date_completed'] 	= Null;
							$final_array[$i]['Training']['date_expired'] 		= Null;
							$newArr['c'][$i] = $final_array[$i];
							$i = $i + 1;	
						}
						
					} else {
							$i = $i + 1;	
							foreach ($get_jobfunction as $get_jobfunction_info)  {
								$final_array[$i]['TrainingType']['id'] 		= '';			
								$final_array[$i]['TrainingType']['name'] 	= '';
								$final_array[$i]['JobFunction']['job_function'] 		= $get_jobfunction_info['JobFunction']['job_function'];
								$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $get_jobfunction_info['JobFunctionTraining']['required_optional'];
								$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= '';
								$final_array[$i]['JobFunctionTraining']['is_delete'] 				= '';			
								$final_array[$i]['Training']['record_created_by']  = 'fresh';		
								
								$final_array[$i]['Training']['index'] 					= 0;
								$final_array[$i]['Training']['id'] 						= '';
								$final_array[$i]['Training']['completed'] 			= '';
								$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
								$final_array[$i]['Training']['date_completed'] 	= Null;
								$final_array[$i]['Training']['date_expired'] 		= Null;
								$newArr1['b'][$i] = $final_array[$i];
								$i = $i + 1;	
							}						
					}
				}
			}
			
			
			// Create New record from expired record
			
			$get_user_trainings	 = $this->Training->find(
				'all',array(
					'conditions'=>array(
						'Training.employee_id'		=> $_POST['employee_id'],
						'Training.date_expire_status'=> 'no',
						'Training.training_completed'=> 'yes',
						'Training.date_expired <'		=>  date('Y-m-d'),
						'Training.date_expired <>'	=>  Null,
						'Training.is_assoc'				=>  'no',						
					),
					'contain'=>array('JobFunction','TrainingType')
				)
			);
		
			//echo "<pre>"; print_r ($get_user_trainings); 	die;
		
			if (!empty($get_user_trainings))  {
				foreach ($get_user_trainings as $info5)  {
					$count = $this->JobFunctionTraining->find ('count',array('conditions'=>array('JobFunctionTraining.training_type_id'=>$info5['TrainingType']['id'],'JobFunctionTraining.job_function_id'=>$info5['JobFunction']['id'])));
					if (!empty($count)) {
						$get_job_fun = $this->JobFunctionTraining->find(
						'first',array(
							'conditions'=>array(
								'JobFunctionTraining.training_type_id'=>$info5['TrainingType']['id'],
								'JobFunctionTraining.job_function_id'=>$info5['JobFunction']['id'],
								'JobFunctionTraining.is_delete'=>'no'
							),
							'contain'=>array()
						)
					);
						//echo "<pre>";print_r($get_job_fun);die;
						$final_array[$i]['TrainingType']['id'] 		= $info5['TrainingType']['id'];			
						$final_array[$i]['TrainingType']['name'] 	= $info5['TrainingType']['name'];
						$final_array[$i]['JobFunction']['job_function'] 		= $info5['JobFunction']['job_function'];
						$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $get_job_fun['JobFunctionTraining']['required_optional'];
						$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= $info5['JobFunction']['id'];
						$final_array[$i]['JobFunctionTraining']['is_delete'] 				= 'no';			
						$final_array[$i]['Training']['record_created_by'] 	= 'expire';
					
						$final_array[$i]['Training']['index'] 					= 1;
						$final_array[$i]['Training']['id'] 						= '';
						$final_array[$i]['Training']['completed'] 			= '';
						$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
						$final_array[$i]['Training']['date_completed'] 	= Null;
						$final_array[$i]['Training']['date_expired'] 		= Null;	
						$newArr2['a'][$i] = $final_array[$i];					
					
						$get_jobfunctionss = $this->JobFunctionTraining->find(
							'all',array(
								'conditions' => array (
									'JobFunctionTraining.training_type_id'=>$info5['TrainingType']['id'],
									'JobFunctionTraining.job_function_id'=>$_POST['content'],
									'JobFunctionTraining.is_delete'=>'no',
									'JobFunction.id <>' =>$info5['JobFunction']['id'],	
									'JobFunctionTraining.create_record'	=>1,
									'TrainingType.id <>'=>Null
								),
								'group' => array('JobFunctionTraining.job_function_id'),
								'contain'   => array (
									'JobFunction'  => array (
										'fields' => array ('id','job_function')
									),
									'TrainingType'  => array (
										'fields' => array ('id','name')
									),
								)
							)
						);
					
										
						if (!empty($get_jobfunctionss))  {	
							$i = $i + 1;
							foreach ($get_jobfunctionss as $get_jobfunction_info2)  {
								$final_array[$i]['TrainingType']['id'] 		= $get_jobfunction_info2['TrainingType']['id'];				
								$final_array[$i]['TrainingType']['name'] 	= $get_jobfunction_info2['TrainingType']['name'];
								$final_array[$i]['JobFunction']['job_function'] 		= $get_jobfunction_info2['JobFunction']['job_function'];
								$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $get_jobfunction_info2['JobFunctionTraining']['required_optional'];
								$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= '';
								$final_array[$i]['JobFunctionTraining']['is_delete'] 				= '';			
								$final_array[$i]['Training']['record_created_by']  = 'fresh';		
								
								$final_array[$i]['Training']['index'] 					= 1;
								$final_array[$i]['Training']['id'] 						= '';
								$final_array[$i]['Training']['completed'] 			= '';
								$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
								$final_array[$i]['Training']['date_completed'] 	= Null;
								$final_array[$i]['Training']['date_expired'] 		= Null;
								$newArr2['a'][$i] = $final_array[$i];
								$i = $i + 1;	
							}								
						}
						$i = $i + 1;	
					}					
				}
			}		
			
			 // Create New record from manual expired record

			$get_user_trainings_manual	 = $this->Training->find(
				'all',array(
					'conditions'=>array(
						'Training.employee_id'		=> $_POST['employee_id'],
						'Training.date_expire_status'=> 'no',
						'Training.training_completed'=> 'yes',
						'Training.date_expired <'		=>  date('Y-m-d'),
						'Training.date_expired <>'	=>  Null,
						'Training.is_assoc'				=>  'no',						
						'Training.job_function_id'		=>  0						
					),
					'contain'=>array('JobFunction','TrainingType')
				)
			);
		
			//echo "<pre>"; print_r ($get_user_trainings); 	die;
		
			if (!empty($get_user_trainings_manual))  {
				foreach ($get_user_trainings_manual as $info6)  {	
					if ($info6['Training']['additional_training_required'] == 'yes')  {
						$additional_training_required	=	0;
					} else  {
						$additional_training_required	=	0;
					}
					$final_array[$i]['TrainingType']['id'] 		= $info6['TrainingType']['id'];			
					$final_array[$i]['TrainingType']['name'] 	= $info6['TrainingType']['name'];
					$final_array[$i]['JobFunction']['job_function'] 		= $info6['JobFunction']['job_function'];
					$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $additional_training_required;
					$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= $info6['JobFunction']['id'];
					$final_array[$i]['JobFunctionTraining']['is_delete'] 				= 'no';			
					$final_array[$i]['Training']['record_created_by'] 	= 'expire';
				
					$final_array[$i]['Training']['index'] 					= 1;
					$final_array[$i]['Training']['id'] 						= '';
					$final_array[$i]['Training']['completed'] 			= '';
					$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
					$final_array[$i]['Training']['date_completed'] 	= Null;
					$final_array[$i]['Training']['date_expired'] 		= Null;	
					$newArr2['a'][$i] = $final_array[$i];					
				
					$get_jobfunctionss = $this->JobFunctionTraining->find(
						'all',array(
							'conditions' => array (
								'JobFunctionTraining.training_type_id'=>$info6['TrainingType']['id'],
								'JobFunctionTraining.job_function_id'=>$_POST['content'],
								'JobFunctionTraining.is_delete'=>'no',
								'JobFunction.id <>' =>$info6['JobFunction']['id'],	
								'JobFunctionTraining.create_record'	=>1,
								'TrainingType.id <>'=>Null
							),
							'group' => array('JobFunctionTraining.job_function_id'),
							'contain'   => array (
								'JobFunction'  => array (
									'fields' => array ('id','job_function')
								),
								'TrainingType'  => array (
									'fields' => array ('id','name')
								),
							)
						)
					);
				
									
					if (!empty($get_jobfunctionss))  {	
						$i = $i + 1;
						foreach ($get_jobfunctionss as $get_jobfunction_info2)  {
							$final_array[$i]['TrainingType']['id'] 		= $get_jobfunction_info2['TrainingType']['id'];				
							$final_array[$i]['TrainingType']['name'] 	= $get_jobfunction_info2['TrainingType']['name'];
							$final_array[$i]['JobFunction']['job_function'] 		= $get_jobfunction_info2['JobFunction']['job_function'];
							$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $get_jobfunction_info2['JobFunctionTraining']['required_optional'];
							$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= '';
							$final_array[$i]['JobFunctionTraining']['is_delete'] 				= '';			
							$final_array[$i]['Training']['record_created_by']  = 'fresh';		
							
							$final_array[$i]['Training']['index'] 					= 1;
							$final_array[$i]['Training']['id'] 						= '';
							$final_array[$i]['Training']['completed'] 			= '';
							$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
							$final_array[$i]['Training']['date_completed'] 	= Null;
							$final_array[$i]['Training']['date_expired'] 		= Null;
							$newArr2['a'][$i] = $final_array[$i];
							$i = $i + 1;	
						}								
					}
					$i = $i + 1;	
				}					
			}
	
			$newarr3 = array_merge($newArr2,$newArr1,$newArr);
			//echo "<pre>";print_r ($newarr3);die;
		}	
		$this->set('data4',$newarr3);
		$this->render();
	}
	
	// We would display the training associated with the removed job function that are not associated to another job function still selected for the employee AND Training Complete = No
	
	public function get_remove_training_edit ()  {
		//Configure::write('debug', 2);
		$this->layout	= false;		
		$i = 0;
		$newArr1		=	array();
		$job_function_trainings		=	array();
		
		if ($_POST)  {			
			//echo "<pre>";print_r ($_POST); 		die;
			
			/*  Get All unique trainings from selected Job Functions */					
			// START
			
			$get_all_job_function_trainings = $this->JobFunctionTraining->find (
				'all',array(
					'conditions'	=> array(
						'JobFunctionTraining.job_function_id'=>$_POST['content'],
						'JobFunctionTraining.create_record'	=>1,
						'JobFunctionTraining.is_delete'		=>'no',
						'TrainingType.id <>'=>Null
					),
					'contain'=>array(
						'TrainingType'	=> array ('fields'=>array('id','name'))
					), 
					'order'	=> array('TrainingType.name'=>'ASC'), 
					'group' 	=> array('TrainingType.id')
				)
			);
			
						
			foreach ($get_all_job_function_trainings as $job_function_training)  {
				if (isset($job_function_training['JobFunctionTraining']['training_type_id']))  {
					array_push ($job_function_trainings,$job_function_training['JobFunctionTraining']['training_type_id']);
				}				
			}
			
			// if (count($job_function_trainings) == 1)  {
				// $job_function_trainings = $job_function_trainings[0];
			// }
			
			//echo "<pre>"; print_r ($job_function_trainings);
			
			// END
			
			
			/*  Get All trainings from removed Job Functions that are not associated to another job function still selected for the employee AND Training Complete = No */
			// START
			
			$get_all_trainings	=	$this->Training->find(
				'all',array (
					'conditions'	=> array (
						'TrainingType.id <>' => Null,	
						'Training.job_function_id'			=> $_POST['removed_job_functions'],
						//'Training.type_id <>'				=> $job_function_trainings,
						'Training.employee_id'			=> $_POST['employee_id'],
						'Training.training_completed'	=> 'no',											
						'Training.is_assoc'					=> 'no',											
					),
					'contain'=>array (
						'JobFunction'	=> array ('fields'=>array('id','job_function')),
						'TrainingType'	=> array ('fields'=>array('id','name'))
					) 
				)
			);					
			
			//echo "<pre>"; print_r($job_function_trainings);
			//echo "<pre>"; print_r($get_all_trainings);die;
			// echo "dd";die;
			
			if (!empty($get_all_trainings))  {
				foreach ($get_all_trainings as $training)  {
					if (in_array($training['TrainingType']['id'],$job_function_trainings))  {
						$final_array[$i]['Training']['training_in_multi_job_fun'] 			= 'yes';
						$final_array[$i]['Training']['repeat_tt'] 			= 0;
						$final_array[$i]['TrainingType']['id'] 				= $training['TrainingType']['id'];		
						$final_array[$i]['JobFunction']['job_function'] 	= $training['JobFunction']['job_function'];
						$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= $training['JobFunction']['id'];
						$final_array[$i]['TrainingType']['name'] 			= $training['TrainingType']['name'];
						$final_array[$i]['JobFunctionTraining']['required_optional'] 		= 1;			
						$final_array[$i]['Training']['id'] 							= $training['Training']['id'];
						$final_array[$i]['Training']['completed'] 			= $training['Training']['training_completed'];
						$final_array[$i]['Training']['date_started'] 		= $training['Training']['date_started'];
						$final_array[$i]['Training']['date_completed'] 	= !empty($training['Training']['date_completed']) ? $training['Training']['date_completed'] : Null;
						$final_array[$i]['Training']['date_expired'] 		= !empty($training['Training']['date_expired']) ? $info2['Training']['date_expired'] : Null;
						$final_array[$i]['Training']['record_created_by']  = 'fresh';		
					
						$newArr1['b'][$i] = $final_array[$i];		
							
						$get_jobfunctions = $this->JobFunctionTraining->find(
							'all',array(
								'conditions' => array (
									'JobFunctionTraining.training_type_id' =>$training['TrainingType']['id'],
									'JobFunctionTraining.job_function_id'  =>$_POST['content'],
									//'JobFunctionTraining.is_delete'=>'no',
									'JobFunction.id <>' =>$training['JobFunction']['id'],	
									'JobFunctionTraining.create_record'	=>1,
									'TrainingType.id <>'=>Null
								),
								'group' 	  => array('JobFunctionTraining.job_function_id'),
								'contain'   => array (
									'JobFunction'  => array (
										'fields' => array ('id','job_function')
									),
									'TrainingType'  => array (
										'fields' => array ('id','name')
									),
								)
							)
						);
							
						//echo "<pre>"; print_r ($get_jobfunctions);die; 
						if (!empty($get_jobfunctions))  {	
							$i = $i + 1;
							foreach ($get_jobfunctions as $get_jobfunction_info1)  {
								if ($get_jobfunction_info1['JobFunctionTraining']['is_delete'] == 'no' or $get_jobfunction_info1['JobFunctionTraining']['is_delete'] == Null)  {	
									$final_array[$i]['Training']['repeat_tt'] 	= 1;
									$final_array[$i]['Training']['training_in_multi_job_fun']	= 'no';
									$final_array[$i]['TrainingType']['id'] 		= $get_jobfunction_info1['TrainingType']['id'];				
									$final_array[$i]['TrainingType']['name'] 	= $get_jobfunction_info1['TrainingType']['name'];
									$final_array[$i]['JobFunction']['job_function'] 				= $get_jobfunction_info1['JobFunction']['job_function'];
									$final_array[$i]['JobFunctionTraining']['required_optional'] 	= $get_jobfunction_info1['JobFunctionTraining']['required_optional'];
									$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= '';
									$final_array[$i]['JobFunctionTraining']['is_delete'] 				= '';			
									$final_array[$i]['Training']['record_created_by']  = 'fresh';		
									
									$final_array[$i]['Training']['id'] 						= '';
									$final_array[$i]['Training']['completed'] 			= '';
									$final_array[$i]['Training']['date_started'] 		= date('Y-m-d');
									$final_array[$i]['Training']['date_completed'] 	= Null;
									$final_array[$i]['Training']['date_expired'] 		= Null;
									$newArr1['b'][$i] = $final_array[$i];
									$i = $i + 1;	
								}								
							}
						}
						$i = $i + 1;	
					}  else  {
						$final_array[$i]['Training']['repeat_tt'] 			= 0;
						$final_array[$i]['Training']['training_in_multi_job_fun'] 			= 'no';
						$final_array[$i]['TrainingType']['id'] 				= $training['TrainingType']['id'];		
						$final_array[$i]['JobFunction']['job_function'] 	= $training['JobFunction']['job_function'];
						$final_array[$i]['JobFunctionTraining']['job_function_id'] 		= $training['JobFunction']['id'];
						$final_array[$i]['TrainingType']['name'] 			= $training['TrainingType']['name'];
						$final_array[$i]['JobFunctionTraining']['required_optional'] 		= 1;			
						$final_array[$i]['Training']['id'] 						= $training['Training']['id'];
						$final_array[$i]['Training']['completed'] 			= $training['Training']['training_completed'];
						$final_array[$i]['Training']['date_started'] 		= $training['Training']['date_started'];
						$final_array[$i]['Training']['date_completed'] 	= !empty($training['Training']['date_completed']) ? $training['Training']['date_completed'] : Null;
						$final_array[$i]['Training']['date_expired'] 		= !empty($training['Training']['date_expired']) ? $info2['Training']['date_expired'] : Null;
						$final_array[$i]['Training']['record_created_by']  = 'fresh';		
					
						$newArr1['b'][$i] = $final_array[$i];			
						$i = $i + 1;	 	
					}					
				}
			}			
		}		

		$this->set('data4',$newArr1);	
		$this->render();
	}
	
	public function remove_training_edit ()  {
		//Configure::write('debug', 2);
		$trainging_ids = array();
		
		if (!empty($this->data))  {				
			//echo "<pre>"; print_r($this->data);die;
			
			foreach ($this->data['Training']['id'] as $info)  {
				array_push($trainging_ids,$info);
			}
			
			if (count($this->data['Training']['disass_id']))  {
				foreach ($this->data['Training']['disass_id'] as $disass_id)  {
					$this->Training->updateAll(
						array(
							'Training.is_assoc'=>"'yes'"
						),
						array(
							'Training.id'=>$disass_id,
						)
					);
				}
			}			
						
			if (count($trainging_ids) == 1)  {
				$trainging_ids = $trainging_ids[0];
			} 
			//echo count($trainging_ids);
			//echo "<pre>"; print_r($trainging_ids);die;
			
			$condition_for_delete3	=	array('Training.id'=>$trainging_ids); 
			if ($this->Training->deleteAll($condition_for_delete3))  {
				die('done');
			}  else  {
				die('error');
			}
		}
		die('done');
	}
	
	
	public function array_sort_by_column_asc(&$arr, $col, $dir = SORT_ASC) 
	{
		$sort_col = array();
		foreach ($arr as $key=> $row) {
			$sort_col[$key] = $row[$col];
		}
		array_multisort($sort_col, $dir, $arr);
	}
	
	public function save_training ()  {
		//echo "<pre>";print_r($this->data);
		if (!empty($this->data))  {
			$training = $this->data['Training'];
			$job_function	=	$this->data['JobFunctionTraining'];
			/*
				Delete tempary data of session user
			*/
			$condition_for_delete2	=	array('TmpTraining.user_id'=>$this->Session->read('user_details.id')); 
			$this->TmpTraining->deleteAll($condition_for_delete2); 
			
			for ($i = 0; $i < count($training['required_optional']); $i++) {
				if($training['completed'][$i] == 'yes') {  
					$completed = 'yes' ; 
				} else {  
					$completed = 'no'; 
				}
				if ($training['create_record'][$i] == 1)  {
					$trainingArr = array();
					$trainingArr['user_id'] 				= $this->Session->read('user_details.id');
					$trainingArr['training_type_id']	= !empty($training['id'][$i]) ? $training['id'][$i] : '';
					$trainingArr['required_optional'] = !empty($training['required_optional'][$i]) ? $training['required_optional'][$i] : 1;
					$trainingArr['completed'] 		= $completed;
					$trainingArr['date_started'] 		= !empty($training['date_started'][$i]) ? $training['date_started'][$i] : '';
					$trainingArr['date_completed']	= !empty($training['date_completed'][$i]) ? $training['date_completed'][$i] : '';
					$trainingArr['date_expired'] 		= !empty($training['date_expired'][$i]) ? $training['date_expired'][$i] : '';
					$trainingArr['job_function_id'] 	= !empty($training['job_function_id'][$i]) ? $training['job_function_id'][$i] : '';
					$this->TmpTraining->create();
					$this->TmpTraining->saveAll($trainingArr);
					//echo "<pre>";print_r($trainingArr);
				}				
			}
		}
		die('Done');
	}
	
	public function save_training_edit ()  {
		//echo "<pre>";print_r($this->data);
		if (!empty($this->data))  {
			$training 			= $this->data['Training'];
			$updateTraining = $this->data['UpdateTraining'];
			
			$data['UpdateTraining']['id']						=	array();
			$data['UpdateTraining']['completed']			=	array();
			$data['UpdateTraining']['date_completed']	=	array();
			$data['UpdateTraining']['date_expired']		=	array();
			
			foreach ($this->data['UpdateTraining']['id'] as $info)  {
				array_push($data['UpdateTraining']['id'],$info);
			}
			
			foreach ($this->data['UpdateTraining']['completed'] as $completed_val)  {
				array_push($data['UpdateTraining']['completed'],$completed_val);
			}
			
			foreach ($this->data['UpdateTraining']['date_completed'] as $date_completed_val)  {
				array_push($data['UpdateTraining']['date_completed'],$date_completed_val);
			}
			
			foreach ($this->data['UpdateTraining']['date_expired'] as $date_expired_val)  {
				array_push($data['UpdateTraining']['date_expired'],$date_expired_val);
			}
			
			//echo count($training['required_optional']);die;
			
			
			
			//echo "<pre>"; print_r ($data);die;
			//$job_function	=	$this->data['JobFunctionTraining'];
			/*
				Delete tempary data of session user
			*/
			$condition_for_delete1	=	array('TmpTraining.user_id'=>$this->Session->read('user_details.id')); 
			$this->TmpTraining->deleteAll($condition_for_delete1);
			
			//echo "<pre>"; print_r ($training);
			//echo "<pre>"; print_r ($updateTraining);die;
			
			for ($i = 0; $i < count($training['required_optional']); $i++) {
				if($training['completed'][$i] == 'yes') {  
					$completed = 'yes' ; 
				} else {  
					$completed = 'no'; 
				}
				
				if ($training['create_record'][$i] == 1)  {
					$trainingArr = array();
					$trainingArr['user_id'] 				= $this->Session->read('user_details.id');
					$trainingArr['training_type_id']	= $training['id'][$i];
					$trainingArr['required_optional'] = !empty($training['required_optional'][$i]) ? $training['required_optional'][$i] : 1;
					$trainingArr['completed'] 		= $completed;
					$trainingArr['date_started'] 		= !empty($training['date_started'][$i]) ? $training['date_started'][$i] : '';
					$trainingArr['date_completed']	= !empty($training['date_completed'][$i]) ? $training['date_completed'][$i] : Null;
					$trainingArr['date_expired'] 		= !empty($training['date_expired'][$i]) ? $training['date_expired'][$i] : Null;
					$trainingArr['job_function_id'] 	= !empty($training['job_function_id'][$i]) ? $training['job_function_id'][$i] : '';
					$trainingArr['record_created_by'] 	= !empty($training['record_created_by'][$i]) ? $training['record_created_by'][$i] : '';
					$this->TmpTraining->create();
					$this->TmpTraining->saveAll($trainingArr);
				}				
			}
			
			for ($j = 0; $j < count($data['UpdateTraining']['id']); $j++)  {
				if (isset($data['UpdateTraining']['completed'][$j]))   {
					if($data['UpdateTraining']['completed'][$j] == 'yes') {  
					$updateTrainingCompleted = 'yes' ; 
					} else {  
						$updateTrainingCompleted = 'no'; 
					}
					
					$updateTrainingArr = array();
					$updateTrainingArr['user_id'] 				= $this->Session->read('user_details.id');
					$updateTrainingArr['training_id'] 			= $data['UpdateTraining']['id'][$j];
					$updateTrainingArr['training_type_id']	= '';
					$updateTrainingArr['required_optional'] = '';
					$updateTrainingArr['completed'] 			= $updateTrainingCompleted;
					$updateTrainingArr['date_started'] 		= Null;
					$updateTrainingArr['date_completed'] 	= !empty($data['UpdateTraining']['date_completed'][$j]) ? $data['UpdateTraining']['date_completed'][$j] : Null;
					$updateTrainingArr['date_expired'] 		= !empty($data['UpdateTraining']['date_expired'][$j]) ? $data['UpdateTraining']['date_expired'][$j] : Null;
					$updateTrainingArr['job_function_id'] 	= '';
					//echo "<pre>";print_r ($updateTrainingArr);die;
					$this->TmpTraining->create();
					$this->TmpTraining->saveAll($updateTrainingArr);
				}  else  {
					$j = $j +1;
				}				
			}			
		}
		die('Done');
	}
	
	public function array_sort_by_column(&$arr, $col, $dir = SORT_DESC) 
	{
		$sort_col = array();
		foreach ($arr as $key=> $row) {
			$sort_col[$key] = $row[$col];
		}
		array_multisort($sort_col, $dir, $arr);
	}

}

?>

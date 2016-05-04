<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    // presentable errors
    $lang['ACCESS_INVALID_STUDY']	    = 'This is an invalid study!';
    $lang['ACCESS_NO_ACCESS_ALLOWED']	= 'Access is not allowed for this study!';
    $lang['ACCESS_INVALID_FOLDER']	= 'This is an invalid folder for this study!';
    $lang['ACCESS_NO_DATAMANAGER']	= 'You have no datamanager rights on any study!';

    // Data processing situations
    $lang['SCAN_OK']		= 'Scanning finished successfully!';
    $lang['SCAN_NOK']		= 'Something went wrong during the scanning process!';
    $lang['LOCK_OK']		= 'The selected files were locked successfully!';
    $lang['LOCK_NOK']	    = 'Something went wrong during the locking process!';
    $lang['UNLOCK_OK']		= 'The selected files were unlocked successfully!';
    $lang['UNLOCK_NOK'] 	= 'Something went wrong during the unlocking process!';
    $lang['VAULT_OK']		= 'The files were transported to the vault successfully!';
    $lang['VAULT_NOK']	    = 'Something went wrong during transportation to the vault!';
?>

		
			<?php if ($header): ?>
			
			 <div class="container">
				<div class="row page-header">
						<div class="col-sm-6">
                            <?php if($userIsAllowed): ?>
                                <h1>
                                    <span class="glyphicon glyphicon-education"></span>
                                    <?php echo htmlentities($title); ?>
                                </h1>
                                <?php echo htmlentities($intakePath . ($studyFolder?'/'.$studyFolder:'')); ?>
                            <?php endif; ?>
						</div>

                        <div class="col-sm-6">
                            <div class="progress_indicator" style="display:none;">
                                <h1 class="pull-right">
                                    Scanning process in progress...
                                </h1>
                                <img class="pull-right" src="<?php echo base_url($this->router->module); ?>/static/images/ajax-loader.gif" style="height:30px;">
                            </div>
                        </div>
				</div>

			<?php endif; ?>

                <!-- <?php if(($alertData = $this->session->userdata('alertOnPageReload'))): ?>
                 <div class="row">
                    <div class="alert alert-<?php echo $alertData->alertType; ?>">
                        <button type="button" class="close" data-hide="alert">&times;</button>
                         <div class="info_text">
                             <?php echo htmlentities($lang[$alertData->alertNr]);  ?>
                             <?php if($alertData->alertSubNr): ?>
                                    <br/>
                                    Error code: <?php echo $alertData->alertSubNr; ?>
                             <?php endif;  ?>
                             <?php if(substr($alertData->alertNr,0,7)=='ACCESS_'): ?>
                                 <br/>
                                 <br/>
                                 <?php if($referenceContext == 'reports' AND $this->studies): ?>
                                     Following studies are accessible for you:
                                     <ul>
                                         <?php foreach($this->studies as $study): ?>
                                            <li>
                                                Go to study <a href="<?php echo site_url("intake"); ?>/reports/index/<?php echo $study; ?>"><?php echo $study; ?></a>
                                            </li>
                                         <?php endforeach; ?>
                                      </ul>
                                 <?php else: ?>
                                    Click <a href="<?php echo base_url() ?>intake">here</a> to go to an area that is accessible for you.
                                 <?php endif; ?>
                             <?php endif; ?>
                         </div>
                    </div>
                    <?php $this->session->unset_userdata('alertOnPageReload'); ?>
                 </div>
                 <?php endif; ?> -->

                 <div class="alert alert-danger">
                 Access denied
                 </div>
                <?php if($this->session->flashdata('error') == true):
                ?>
                    <div class="alert alert-<?=$this->session->flashdata('alert');?>">
                        <?=$this->session->flashdata('message');?>
                    </div>
                <?php
                endif;
                ?>

                <div>
                    <ul>
                        
                    </ul>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-default dropdown-toggle" 
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Change study <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach($studies as $study):
                            $class = $study == $studyID ? 'glyphicon-ok' : 'pad-left';
                            // $str = "<li><div class=\"glyphicon glyphicon-ok\"><a href=\"/intake-ilab/intake/index/%s\">%s</a></div></li>";
                            $str = "<li ><a class=\"glyphicon %s\" href=\"/intake-ilab/intake/index/%s\">&nbsp;%s</a></li>";
                            echo sprintf($str, $class, $study, $study);
                        endforeach;
                        ?>
                    </ul>
                </div>

				<?php $this->load->view($content); ?>

			</div>
		</div>



        <div class="modal fade" id="dialog-ok" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" >
                    <div class="modal-header">
                        <h3 class="no-offset"></h3>
                    </div>
                    <div class="modal-body">
                        <span class="glyphicon glyphicon-info-sign"></span>
                        <div class="col-sm-10 pull-right">
                            <span class="item"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <span class="glyphicon glyphicon-ok"></span> OK
                        </button>
                    </div>
                </div>
            </div>
        </div>

        

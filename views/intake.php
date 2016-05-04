<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ($header && $hasStudies): ?>

<div class="container">
  <div class="row page-header">
    <div class="col-sm-6">
      <h1>
        <span class="glyphicon glyphicon-education"></span>
        <?php echo htmlentities($title); ?>
      </h1>
      <?php if($userIsAllowed) echo htmlentities($intakePath . ($studyFolder?'/'.$studyFolder:'')); ?>
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

<?php if($information = $this->session->userdata('information')): ?>
  <div class="alert alert-<?=$information->type;?>">
    <?=$information->message;?>
  </div>

  <?php 
  $this->session->unset_userdata('information');
  endif;
  ?>

  <?php if($hasStudies): ?>
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
<?php endif;?>

<?php $this->load->view($content); ?>

</div>
</div>



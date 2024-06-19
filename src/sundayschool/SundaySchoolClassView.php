<?php

require '../Include/Config.php';
require '../Include/Functions.php';

use ChurchCRM\Authentication\AuthenticationManager;
use ChurchCRM\dto\SystemConfig;
use ChurchCRM\dto\SystemURLs;
use ChurchCRM\Service\SundaySchoolService;
use ChurchCRM\Utils\InputUtils;
use ChurchCRM\Utils\MiscUtils;

$sundaySchoolService = new SundaySchoolService();

$iGroupId = '-1';
$iGroupName = 'Unknown';
if (isset($_GET['groupId'])) {
    $iGroupId = InputUtils::legacyFilterInput($_GET['groupId'], 'int');
}

$sSQL = 'select * from group_grp where grp_ID =' . $iGroupId;
$rsSundaySchoolClass = RunQuery($sSQL);
while ($aRow = mysqli_fetch_array($rsSundaySchoolClass)) {
    $iGroupName = $aRow['grp_Name'];
}

$birthDayMonthChartArray = [];
foreach ($sundaySchoolService->getKidsBirthdayMonth($iGroupId) as $birthDayMonth => $kidsCount) {
    $birthDayMonthChartArray[] = [
        gettext($birthDayMonth),
        $kidsCount
    ];
}
$birthDayMonthChartJSON = json_encode($birthDayMonthChartArray, JSON_THROW_ON_ERROR);

$genderChartArray = [];
foreach ($sundaySchoolService->getKidsGender($iGroupId) as $gender => $kidsCount) {
    if ($kidsCount === 0) {
        continue;
    }
    $genderChartArray[] = [
        'label' => gettext($gender),
        'data' => $kidsCount
    ];
}
$genderChartJSON = json_encode($genderChartArray, JSON_THROW_ON_ERROR);

$rsTeachers = $sundaySchoolService->getClassByRole($iGroupId, 'Teacher');
$sPageTitle = gettext('Sunday School') . ': ' . $iGroupName;

$TeachersEmails = [];
$KidsEmails = [];
$ParentsEmails = [];

$thisClassChildren = $sundaySchoolService->getKidsFullDetails($iGroupId);

foreach ($thisClassChildren as $child) {
    if (!empty($child['dadEmail'])) {
        $ParentsEmails[] = $child['dadEmail'];
    }
    if (!empty($child['momEmail'])) {
        $ParentsEmails[] = $child['momEmail'];
    }
    if (!empty($child['kidEmail'])) {
        $KidsEmails[] = $child['kidEmail'];
    }
}

foreach ($rsTeachers as $teacher) {
    $TeachersEmails[] = $teacher['per_Email'];
}

require '../Include/Header.php';

?>

<div class="card">
  <div class="card-header with-border">
    <h3 class="card-title"><?= gettext('Sunday School Class Functions') ?></h3>
  </div>
  <div class="card-body">
    <?php
    $sMailtoDelimiter = AuthenticationManager::getCurrentUser()->getUserConfigString("sMailtoDelimiter");
    $allEmails = array_unique([...$ParentsEmails, ...$KidsEmails, ...$TeachersEmails]);
    $roleEmails = [];
    $roleEmails['Parents'] = implode($sMailtoDelimiter, $ParentsEmails) . ',';
    $roleEmails['Teachers'] = implode($sMailtoDelimiter, $TeachersEmails) . ',';
    $roleEmails['Kids'] = implode($sMailtoDelimiter, $KidsEmails) . ',';
    $sEmailLink = implode($sMailtoDelimiter, $allEmails) . ',';
    // Add default email if default email has been set and is not already in string
    if (SystemConfig::getValue('sToEmailAddress') != '' && !stristr($sEmailLink, (string) SystemConfig::getValue('sToEmailAddress'))) {
        $sEmailLink .= $sMailtoDelimiter . SystemConfig::getValue('sToEmailAddress');
    }
    $sEmailLink = urlencode($sEmailLink);  // Mailto should comply with RFC 2368

    if (AuthenticationManager::getCurrentUser()->isEmailEnabled()) { // Does user have permission to email groups
      // Display link
        ?>
      <div class="btn-group">
        <a class="btn btn-app" href="mailto:<?= mb_substr($sEmailLink, 0, -3) ?>"><i
            class="fa fa-paper-plane"></i><?= gettext('Email') ?></a>
        <button type="button" class="btn btn-app dropdown-toggle" data-toggle="dropdown">
          <span class="caret"></span>
          <span class="sr-only"><?= gettext('Toggle Dropdown') ?></span>
        </button>
        <ul class="dropdown-menu" role="menu">
          <?php generateGroupRoleEmailDropdown($roleEmails, 'mailto:') ?>
        </ul>
      </div>

      <div class="btn-group">
        <a class="btn btn-app" href="mailto:?bcc=<?= mb_substr($sEmailLink, 0, -3) ?>"><i
            class="fa-regular fa-paper-plane"></i><?= gettext('Email (BCC)') ?></a>
        <button type="button" class="btn btn-app dropdown-toggle" data-toggle="dropdown">
          <span class="caret"></span>
          <span class="sr-only"><?= gettext('Toggle Dropdown') ?></span>
        </button>
        <ul class="dropdown-menu" role="menu">
          <?php generateGroupRoleEmailDropdown($roleEmails, 'mailto:?bcc=') ?>
        </ul>
      </div>
        <?php
    }
    ?>
    <!-- <a class="btn btn-success" data-toggle="modal" data-target="#compose-modal"><i class="fas fa-pen"></i> Compose Message</a>  This doesn't really work right now...-->
    <a class="btn btn-app" href="../GroupView.php?GroupID=<?= $iGroupId ?>"><i
        class="fa fa-user-plus"></i><?= gettext('Add Students') ?> </a>

    <a class="btn btn-app" href="../GroupEditor.php?GroupID=<?= $iGroupId?>"><i class="fas fa-pen"></i><?= gettext("Edit this Class") ?></a>
  </div>
</div>

<div class="card card-success">
  <div class="card-header">
    <h3 class="card-title"><?= gettext('Teachers') ?></h3>
  </div>
  <!-- /.box-header -->
  <div class="card-body row">
    <?php foreach ($rsTeachers as $teacher) {
        ?>
      <div class="col-sm-2">
        <!-- Begin user profile -->
        <div class="card card-info text-center user-profile-2">
          <div class="user-profile-inner">
            <h4 class="white"><?= $teacher['per_FirstName'] . ' ' . $teacher['per_LastName'] ?></h4>
            <img src="<?= SystemURLs::getRootPath(); ?>/api/person/<?= $teacher['per_ID'] ?>/thumbnail"
                  alt="User Image" class="user-image initials-image" width="85" height="85" />
            <a href="mailto:<?= $teacher['per_Email'] ?>" type="button" class="btn btn-primary btn-sm btn-block"><i
                class="fa fa-envelope"></i> <?= gettext('Send Message') ?></a>
            <a href="../PersonView.php?PersonID=<?= $teacher['per_ID'] ?>" type="button"
               class="btn btn-primary btn-info btn-block"><i class="fa fa-q"></i><?= gettext('View Profile') ?></a>
          </div>
        </div>
      </div>
        <?php
    } ?>
  </div>
</div>

<div class="card card-info">
  <div class="card-header">
    <h3 class="card-title"><?= gettext('Quick Status') ?></h3>

    <div class="card-tools pull-right">
      <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
    </div>
  </div>
  <!-- /.box-header -->
  <div class="card-body row">
    <div class="col-lg-8">
      <!-- Bar chart -->
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title"><?= gettext('Birthdays by Month') ?></h3>
            <div class="card-tools">
                <i class="fa fa-chart-bar"></i>
            </div>
        </div>
        <div class="card-body">
          <div class="disableSelection">
              <canvas id="bar-chart"></canvas>
          </div>
        </div>
        <!-- /.box-body-->
      </div>
      <!-- /.box -->
    </div>
    <div class="col-lg-4">
      <!-- Donut chart -->
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title"><?= gettext('Gender') ?></h3>
            <div class="card-tools">
                <i class="fa fa-chart-bar"></i>
            </div>
        </div>
        <div class="card-body">
          <canvas id="donut-chart"></canvas>
        </div>
        <!-- /.box-body-->
      </div>
      <!-- /.box -->
    </div>
  </div>
</div>

<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title"><?= gettext('Students') ?></h3>
  </div>
  <!-- /.box-header -->
  <div class="card-body table-responsive">
    <h4 class="birthday-filter" style="display:none;"><?= gettext('Showing students with birthdays in') ?><span class="month"></span> <i style="cursor:pointer; color:red;" class="icon fa fa-close"></i></h4>
    <table id="sundayschool" class="table table-striped table-bordered data-table" cellspacing="0" width="100%">
      <thead>
      <tr>
        <th></th>
        <th><?= gettext('Name') ?></th>
        <th><?= gettext('Birth Date') ?></th>
        <th><?= gettext('Age') ?></th>
        <th><?= gettext('Email') ?></th>
        <th><?= gettext('Mobile') ?></th>
        <th><?= gettext('Home Phone') ?></th>
        <th><?= gettext('Home Address') ?></th>
        <th><?= gettext('Dad Name') ?></th>
        <th><?= gettext('Dad Mobile') ?></th>
        <th><?= gettext('Dad Email') ?></th>
        <th><?= gettext('Mom Name') ?></th>
        <th><?= gettext('Mom Mobile') ?></th>
        <th><?= gettext('Mom Email') ?></th>
      </tr>
      </thead>
      <tbody>
      <?php

        foreach ($thisClassChildren as $child) {
            $hideAge = $child['flags'] == 1 || empty($child['birthYear']);
            $birthDate = MiscUtils::formatBirthDate($child['birthYear'], $child['birthMonth'], $child['birthDay'], '-', $child['flags']); ?>
          <tr>
          <td>
            <img src="<?= SystemURLs::getRootPath(); ?>/api/person/<?= $child['kidId'] ?>/thumbnail"
                alt="User Image" class="user-image initials-image" style="width: <?= SystemConfig::getValue('iProfilePictureListSize') ?>px !; height: <?= SystemConfig::getValue('iProfilePictureListSize') ?>px; max-width:none" />
          </td>
          <td><a href="<?= SystemURLs::getRootPath(); ?>/PersonView.php?PersonID=<?= $child['kidId'] ?>"><?= $child['LastName'] . ', ' . $child['firstName'] ?></a></td>
          <td><?= $birthDate ?> </td>
          <td><?= MiscUtils::formatAge($child['birthMonth'], $child['birthDay'], $child['birthYear']) ?></td>
          <td><?= $child['kidEmail'] ?></td>
          <td><?= $child['mobilePhone'] ?></td>
          <td><?= $child['homePhone'] ?></td>
          <td><?= $child['Address1'] . ' ' . $child['Address2'] . ' ' . $child['city'] . ' ' . $child['state'] . ' ' . $child['zip'] ?></td>
          <td><a href='<?= SystemURLs::getRootPath(); ?>/PersonView.php?PersonID=<?= $child['dadId'] ?>'><?= $child['dadFirstName'] . ' ' . $child['dadLastName'] ?></a></td>
          <td><?= $child['dadCellPhone'] ?></td>
          <td><?= $child['dadEmail'] ?></td>
          <td><a href='<?= SystemURLs::getRootPath(); ?>/PersonView.php?PersonID=<?= $child['momId'] ?>'><?= $child['momFirstName'] . ' ' . $child['momLastName'] ?></td>
          <td><?= $child['momCellPhone'] ?></td>
          <td><?= $child['momEmail'] ?></td>
          </tr>

            <?php
        }

        ?>
      </tbody>
    </table>
  </div>
</div>

<?php
function implodeUnique($array, $withQuotes): string
{
          array_unique($array);
          asort($array);
    if (count($array) > 0) {
        if ($withQuotes) {
            $string = implode("','", $array);

            return "'" . $string . "'";
        } else {
            return implode(',', $array);
        }
    }

          return '';
}

?>

<!-- COMPOSE MESSAGE MODAL -->
<div class="modal fade" id="compose-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content large">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><i class="fa fa-envelope"></i><?= gettext('Compose New Message') ?></h4>
      </div>
      <form action="SendEmail.php" method="post">
        <div class="modal-body">
          <div class="form-group">
            <label><?= gettext('Kids Emails') ?></label>
            <input name="email_to" class="form-control email-recipients-kids"
                   value="<?= implodeUnique($KidsEmails, false) ?>">
          </div>
          <div class="form-group">
            <label><?= gettext('Parents Emails') ?></label>
            <input name="email_to_2" class="form-control email-recipients-parents"
                   value="<?= implodeUnique($ParentsEmails, false) ?>">
          </div>
          <div class="form-group">
            <label><?= gettext('Teachers Emails') ?></label>
            <input name="email_cc" class="form-control email-recipients-teachers"
                   value="<?= implodeUnique($TeachersEmails, false) ?>">
          </div>
          <div class="form-group">
            <textarea name="message" id="email_message" class="form-control" placeholder="Message"
                      style="height: 120px;"></textarea>
          </div>
          <div class="form-group">
            <div class="btn btn-success btn-file">
              <i class="fa fa-paperclip"></i><?= gettext('Attachment') ?>
              <input type="file" name="attachment"/>
            </div>
            <p class="help-block"><?= gettext('Max. 32MB') ?></p>
          </div>

        </div>
        <div class="modal-footer clearfix">

          <button type="button" class="btn btn-danger" data-dismiss="modal"><i
              class="fa fa-times"></i><?= gettext('Discard') ?></button>

          <button type="submit" class="btn btn-primary pull-left"><i
              class="fa fa-envelope"></i><?= gettext('Send Message') ?></button>
        </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- chartjs -->
<script  src="<?= SystemURLs::getRootPath() ?>/skin/external/chartjs/chart.umd.js"></script>

<script nonce="<?= SystemURLs::getCSPNonce() ?>">
  $(function () {

    var dataTable = $('.data-table').DataTable(window.CRM.plugin.dataTable);

    // turn the element to select2 select style
    $('.email-recipients-kids').select2({
      placeholder: 'Enter recipients',
      tags: [<?php implodeUnique($KidsEmails, true) ?>]
    });
    $('.email-recipients-teachers').select2({
      placeholder: 'Enter recipients',
      tags: [<?= implodeUnique($TeachersEmails, true) ?>]
    });
    $('.email-recipients-parents').select2({
      placeholder: 'Enter recipients',
      tags: [<?= implodeUnique($ParentsEmails, true) ?>]
    });

    var birthDateColumn = dataTable.column(':contains(Birth Date)');

    function hideBirthDayFilter() {
      birthDateColumn
        .search('')
        .draw();

      birthDayFilter.hide();
    }

    var birthDayFilter = $('.birthday-filter');
    var birthDayMonth = birthDayFilter.find('.month');
    birthDayFilter.find('i.fa-close')
      .bind('click', hideBirthDayFilter);

    document.getElementById('bar-chart').onclick = function(event) {
      var activePoints = barChart.getElementsAtEvent(event);

      // If no active points, hide the filter and return
      if (activePoints.length === 0) {
          hideBirthDayFilter();
          return;
      }

      var monthIndex = activePoints[0]._index;
      var month = barChart.data.labels[monthIndex];

      // Update filter text
      birthDayMonth.text(month);
      birthDayFilter.show();

      // Highlight the selected bar
      activePoints.forEach(function(point) {
          // Apply highlight styling as needed
          point.custom = point.custom || {};
          point.custom.backgroundColor = 'red';
      });

      barChart.update();
    };
  });

  /*
   * BAR CHART
   * ---------
   */
  var barData = <?= $birthDayMonthChartJSON ?>;
  var barLabels = barData.map(data => data[0]);
  var barValues = barData.map(data => data[1]);
  var maxBarValue = Math.max(...barValues);

  var barChartConfig = {
      type: 'bar',
      data: {
          labels: barLabels,
          datasets: [{
              label: 'Birthdays by Month',
              borderColor: '#3c8dbc',
              backgroundColor: '#9ec5de',
              borderWidth: 2,
              data: barValues
          }]
      },
      options: {
        scales: {
          y: {
            max: maxBarValue + 1,
            beginAtZero: true,
            ticks: {
              stepSize: 1,
            }
          }
        }
      }
  };

  var barChart = new Chart(document.getElementById('bar-chart'), barChartConfig);

  /* END BAR CHART */

  /*
   * DONUT CHART
   * -----------
   */

  var donutData = <?= $genderChartJSON ?>;
  var donutLabels = donutData.map(data => data.label);
  var donutValues = donutData.map(data => data.data);

  var donutChartConfig = {
      type: 'doughnut',
      data: {
          labels: donutLabels,
          datasets: [{
              data: donutValues,
              backgroundColor: ['#3c8dbc', '#ff851b']
          }]
      },
      options: {
          legend: {
              position: 'bottom'
          }
      }
  };

  var donutChart = new Chart(document.getElementById('donut-chart'), donutChartConfig);
  /*
   * END DONUT CHART
   */
  /*
   * Custom Label formatter
   * ----------------------
   */
  function labelFormatter(label, series) {
    return "<div style='font-size:13px; text-align:center; padding:2px; color: #fff; font-weight: 600;'>"
      + label
      + "<br/>"
      + Math.round(series.percent) + "%</div>";
  }

</script>
<?php
require '../Include/Footer.php';

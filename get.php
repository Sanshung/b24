<?php

use Bitrix\Disk\Uf\LocalDocumentController;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
global $USER;
$USER->Authorize(1);
/* sql
$strSql = "SELECT * FROM a_importer WHERE `NEW` = 'Y' AND `TITLE` <> '' AND `CODE` <> '' LIMIT 300";
$res = $DB->Query($strSql, false, $err_mess . __LINE__);
while ($row = $res->Fetch()) {
    $itemsToAdd[] = $row;
}
*/
/*global $DB;
$DB->Query("SET @@session.time_zone = '+06:00'");
date_default_timezone_set('Asia/Almaty');
$connection = \Bitrix\Main\Application::getConnection();
$connection->queryExecute("SET @@session.time_zone = '+06:00'");*/

function print_r2($arr)
{
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

CModule::IncludeModule("socialnetwork");
CModule::IncludeModule("forum");
CModule::IncludeModule('tasks');
\Bitrix\Main\Loader::includeModule('disk');


$get = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$arResult = array();
if ($get['metod'] == 'task-test'):

    CModule::IncludeModule('tasks');

    /* //комментарии
        $arFilter['=ID'] = 31;
        $hGTasks = CTasks::GetList(array(), $arFilter, array('*', 'UF_*'));
        while($row = $hGTasks->Fetch())
        {
            $arGTasks[$row['ID']] = array('ID'=>$row['ID'], 'XML_ID'=>$row['XML_ID'], 'STATUS'=>$row['STATUS'], 'TITLE'=>$row['TITLE'], 'UF_TASKS_TP_NAME'=>$row['UF_TASKS_TP_NAME'], 'UF_TASK_TP_CODE'=>$row['UF_TASK_TP_CODE'], 'UF_TASK_TP_ADRES'=>$row['UF_TASK_TP_ADRES'], 'CREATED_DATE'=>$row['CREATED_DATE'], 'DEADLINE'=>$row['DEADLINE'], 'CLOSED_DATE'=>$row['CLOSED_DATE'], 'GROUP_NAME'=>'');

            if ($row['GROUP_ID'] && array_key_exists($row['GROUP_ID'], $arGroups))
                $arGTasks[$row['ID']]['GROUP_NAME'] = $arGroups[$row['GROUP_ID']]['NAME'].'['.$row['GROUP_ID'].']';

            if ($row['FORUM_ID'] && $row['FORUM_TOPIC_ID'])
            {
                //только не спрашивайте что такое "ТК", для этого надо понимать логику разработчика
                $hComments = CForumMessage::GetListEx(array("ID"=>"ASC"), array("FORUM_ID"=>$row['FORUM_ID'], 'TOPIC_ID'=>$row['FORUM_TOPIC_ID'], "!PARAM1" => 'TK'));
                while ($line = $hComments->Fetch())
                    print_r($line);
                //$arGTasks[$row['ID']]['COMMENTS'][$line['ID']] = array('POST_DATE'=>$line['POST_DATE'], 'POST_MESSAGE'=>$line['POST_MESSAGE']);
            }
        }*/

    //https://dev.1c-bitrix.ru/api_help/tasks/classes/ctaskitem/index.php
    $strSql = "SELECT ID, NAME, IMAGE_ID, PROJECT_DATE_START, PROJECT_DATE_FINISH FROM b_sonet_group"; //проекты
    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        $arStatus[$row['ID']] = $row['STATUS_ID'];
    }
    $strSql = "SELECT * FROM b_crm_status"; //статусы
    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        $arStatus[$row['ID']] = $row['STATUS_ID'];
    }
    $strSql = "SELECT * FROM b_tasks_member";//пользователи
    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        $arStatus[$row['ID']] = $row['STATUS_ID'];
    }

    $strSql = "SELECT ID, TITLE, DESCRIPTION, STATUS, DATE_START, DEADLINE, START_DATE_PLAN, END_DATE_PLAN, GROUP_ID, PARENT_ID,  FROM b_tasks "; //задачи
    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        $arResult['task'] = $row;
    }

endif; ///task
if ($get['metod'] == 'task-file'):

    $strSql = "SELECT bdao.ID, bdao.OBJECT_ID, bdo.FILE_ID as BDO_FILE_ID, bdao.MODULE_ID, bf.ID as FILE_ID FROM
            b_disk_attached_object bdao
        
        INNER JOIN b_disk_object bdo
         ON bdao.OBJECT_ID = bdo.ID
        INNER JOIN b_file bf
         ON bdo.FILE_ID = bf.ID
        WHERE
        bdao.MODULE_ID = 'forum'
        ";
    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        //print_r2($row);
        $arFileOb[$row['ID']] = $row['FILE_ID'];
    }

    $arFilter = array();

    if(!empty($get['project_id']))
    {
        $arFilter['GROUP_ID']= $get['project_id'];
    }
    $hGTasks = CTasks::GetList(array(), $arFilter, array('*', 'UF_*'));
    while ($row = $hGTasks->Fetch()) {

        //print_r2($row); die();

        /*$arGTasks[$row['ID']] = array('ID'=>$row['ID'], 'XML_ID'=>$row['XML_ID'], 'STATUS'=>$row['STATUS'], 'TITLE'=>$row['TITLE'], 'UF_TASKS_TP_NAME'=>$row['UF_TASKS_TP_NAME'], 'UF_TASK_TP_CODE'=>$row['UF_TASK_TP_CODE'], 'UF_TASK_TP_ADRES'=>$row['UF_TASK_TP_ADRES'], 'CREATED_DATE'=>$row['CREATED_DATE'], 'DEADLINE'=>$row['DEADLINE'], 'CLOSED_DATE'=>$row['CLOSED_DATE'], 'GROUP_NAME'=>'');

        if ($row['GROUP_ID'] && array_key_exists($row['GROUP_ID'], $arGroups))
            $arGTasks[$row['ID']]['GROUP_NAME'] = $arGroups[$row['GROUP_ID']]['NAME'].'['.$row['GROUP_ID'].']';*/
        $arPhoto = array();
        $arDocs = array();

        if ($row['FORUM_ID'] && $row['FORUM_TOPIC_ID']) {
            //только не спрашивайте что такое "ТК", для этого надо понимать логику разработчика

            $hComments = CForumMessage::GetListEx(array("ID" => "ASC"), array("FORUM_ID" => $row['FORUM_ID'], 'TOPIC_ID' => $row['FORUM_TOPIC_ID'], "!PARAM1" => 'TK', '>POST_DATE' => date('d.m.Y H:i:s', $get['POST_DATE'])));
            while ($line = $hComments->Fetch()) {

                $arData['ATTACHED_OBJECTS'] = array();
                $attachedObjects = \Bitrix\Disk\Internals\AttachedObjectTable::getList(
                    array(
                        'select' => array('ID'),
                        'filter' => array(
                            '=ENTITY_TYPE' => 'Bitrix\Disk\Uf\ForumMessageConnector',
                            '=MODULE_ID' => 'forum',
                            '=VERSION_ID' => null,
                            '=ENTITY_ID' => $line['ID']
                        )
                    )
                );


                $arFilePhotos = array();
                $arFileDocs = array();
                foreach ($attachedObjects as $attachedObject) {



                    //$arData['ATTACHED_OBJECTS'][] = CFile::GetPath($arFileOb[$attachedObject['ID']]);
                    $fArr = CFile::GetFileArray($arFileOb[$attachedObject['ID']]);
                    //print_r2($fArr);
                    $newFile = '/web-service/file/' . $row['ID'] . '_' . $fArr['ORIGINAL_NAME'];
                    /*if($_GET['d'] == true && $row['ID'] == 2842)
                    {

                        print_r2(array($arFileOb, $attachedObject['ID']));

                    }*/
                    if (!empty($fArr)) {
                        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $newFile)) {

                            if (!copy($_SERVER['DOCUMENT_ROOT'] . $fArr['SRC'], $_SERVER['DOCUMENT_ROOT'] . $newFile)) {
                                //print_r2(array($_SERVER['DOCUMENT_ROOT'].$fArr['SRC'], $_SERVER['DOCUMENT_ROOT'].$newFile));
                            }
                        }

                        if (CFile::IsImage($fArr['ORIGINAL_NAME'])) {
                            $arFilePhotos[] = array(
                                'url'=>'http://project.bko.gov.kz' . $newFile,
                                'name' =>preg_replace('/(.*)\\.[^\\.]*/', '$1',$fArr['ORIGINAL_NAME'])
                            );
                        } else {
                            $arFileDocs[] = array(
                                'url'=>'http://project.bko.gov.kz' . $newFile,
                                'name' =>preg_replace('/(.*)\\.[^\\.]*/', '$1',$fArr['ORIGINAL_NAME'])
                            );
                        }
                    }
                    // print_r2();
                    //print_r2(CFile::GetPath($arFileOb[$attachedObject['ID']]));


                }
                $pattern = '/\[(.+?)\]/';
                preg_match_all($pattern, substr( $line['POST_MESSAGE'],3), $matches);
                $matches[0][] = '\n ';
                if (!empty($arFilePhotos)) {
                    $arPhoto[] = array(
                        "date" => $line['POST_DATE'],
                        "message" => str_replace($matches[0], '', $line['POST_MESSAGE']),
                        "files" => $arFilePhotos
                    );
                }
                if (!empty($arFileDocs)) {
                    $arDocs[] = array(
                        "date" => $line['POST_DATE'],
                        "message" => str_replace($matches[0], '', $line['POST_MESSAGE']),
                        "files" => $arFileDocs
                    );
                }


                //if(!empty($arData['ATTACHED_OBJECTS']))
                // $arResult['task-file'][$row['GROUP_ID']][$line['ID']] = array('POST_DATE'=>$line['POST_DATE'], 'POST_MESSAGE'=>$line['POST_MESSAGE'], 'FILE' =>$arData['ATTACHED_OBJECTS'] );

            }
        }
        if ((!empty($arPhoto) or !empty($arDocs)) && $row['GROUP_ID'] > 0) {


            if (!empty($arPhoto)) {
                $arProject[$row['GROUP_ID']]['file']["photos"] = array_merge((array)$arPhoto, (array)$arProject[$row['GROUP_ID']]['file']["photos"]);
            }
            if (!empty($arDocs)) {
                $arProject[$row['GROUP_ID']]['file']["docs"] = array_merge((array)$arDocs, (array)$arProject[$row['GROUP_ID']]['file']["docs"]);
            }


        }


        /* $arProject = array(
                "project" => $row['GROUP_ID'],
            );
        */
        //$arResult[] = $arProject;
        ///print_r2($row);
    }
    foreach ($arProject as $projectID => $item)
    {
        //print_r2(array($projectID, $item));
        $arResult[] = array_merge(array("project" => $projectID), (array)$item['file']);

    }
    if($_GET['d'] == true)
    {


        die();

    }

endif;
if ($get['metod'] == 'projects-handbook'):

    $strSql = "SELECT ID, VALUE, USER_FIELD_ID  FROM b_user_field_enum"; //задачи
    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        $arUserFiled[$row['USER_FIELD_ID']][$row['ID']] = $row['VALUE'];
    }
    /*echo '<pre>';
    print_r($arUserFiled);*/
    $rsUsers = CUser::GetList(($by="personal_country"), ($order="desc"), $filter); // выбираем пользователей
    while($arUsers = $rsUsers->GetNext()) :
        $user[] = array('ID'=>$arUsers['ID'], 'NAME' => $arUsers['LAST_NAME'].' '.$arUsers['NAME'].' '.$arUsers['SECOND_NAME']);
    endwhile;

    $arResult['UF_PROJ_GU'] = $arUserFiled[98];
    $arResult['UF_PROJ_PORTFEL'] = $arUserFiled[115];
    $arResult['UF_PROJ_KURATOR'] = $arUserFiled[97];
    $arResult['UF_PROJ_GU'] = $arUserFiled[98];
    $arResult['PROJECT_STATUS'] = $arUserFiled[116];
    $arResult['UF_PROJ_NA_KANTROLE'] = $arUserFiled[118];
    $arResult['UF_PROJ_MODERATOR'] = $user;
    $arResult['TASK_STATUS'] = array(
        '-2' => 'Новая задача (не просмотрена).',
        '-1' => 'Задача просрочена.',
        '-3' => 'Задача почти просрочена.',
        '1' => 'Новая задача. (Не используется)',
        '2' => 'Задача принята ответственным. (Не используется)',
        '3' => 'Задача выполняется.',
        '4' => 'Условно завершена (ждет контроля постановщиком).',
        '5' => 'Задача завершена.',
        '6' => 'Задача отложена.',
        '7' => 'Задача отклонена ответственным. (Не используется)',
    );

    $userOb = CUser::GetList(($by="personal_country"), ($order="desc"), $filter);
    while ($arUser = $userOb->Fetch())
        $arResult['OWNER_ID'][$arUser['ID']] = $arUser['NAME'].' '.$arUser['LAST_NAME'];
endif;
if ($get['metod'] == 'projects'):

    CModule::IncludeModule("socialnetwork");
    $strSql = "SELECT ID, VALUE  FROM b_user_field_enum"; //задачи
    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        $arUserFiled[$row['ID']] = $row['VALUE'];
    }

    $arResult['projects-desc'] = array(
        'NAME' => 'Наименование',
        'IMAGE' => 'Фото',
        'PROJECT_DATE_START' => 'Дата начала',
        'PROJECT_DATE_FINISH' => 'Дата завершение',
        'OWNER_ID' => 'Руководитель',
        'UF_PROJ_PORTFEL' => 'Портфель',
        'UF_PROJ_GU' => 'Государственное учереждение',
        'UF_PROJ_KURATOR' => 'Куратор',
        'UF_PROJ_PASSPORT' => 'Паспорт проекта',
        'UF_PROJ_PLAN' => 'План проекта',
        'UF_PROJ_BUDGET_VIDEL' => 'Выделенный бюджет',
        'UF_PROJ_BUDGET_OSVOE' => 'Освоенный бюджет',
        'UF_PROJ_BUDGET_DOPOL' => 'Дополнительная потребность',
        'UF_PROJ_BUDGET' => 'Бюджет проекта',
        'PROJECT_STATUS' => 'Статус',
        'UF_PROJ_PODRYADCHIK' => 'Подрядная организация',
        'UF_PROJ_MODERATOR' => 'Модератор',


    );
    $arUpdate = $DB->PrepareUpdate("b_sonet_group", array('DATE_UPDATE' =>  date('d.m.Y H:i:s', $get['POST_DATE'])));
    $strSql = "SELECT NAME, ID, PROJECT_DATE_START, PROJECT_DATE_FINISH FROM b_sonet_group WHERE ".str_replace('=', '>', $arUpdate)." "; //проекты

    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        $arr = \Bitrix\Socialnetwork\Item\Workgroup::getById($row['ID']);
        $arr = $arr->getFields();

        //$rsUserO = CUser::GetByID($arr['OWNER_ID']);


        /*$arr['UF_PROJ_PORTFEL'] = $arr['UF_PROJ_PORTFEL']['VALUE'] == false ? 'Новые' : $arUserFiled[$arr['UF_PROJ_PORTFEL']['VALUE']];
        $arr['UF_PROJ_KURATOR'] = $arUserFiled[$arr['UF_PROJ_KURATOR']['VALUE']];*/
        $arr['UF_PROJ_PORTFEL'] = $arr['UF_PROJ_PORTFEL']['VALUE'];
        $arr['UF_PROJ_KURATOR'] = $arr['UF_PROJ_KURATOR']['VALUE'];

        $arResult['projects'][] = array(
            'ID' => $row['ID'],
            'NAME' => $row['NAME'], //Наименование
            'IMAGE' => CFile::GetPath($arr['IMAGE_ID']),//Фото
            'PROJECT_DATE_START' => date('Y-m-d', strtotime($row['PROJECT_DATE_START']) + (3 * 60 * 60)),//Дата начала
            'PROJECT_DATE_FINISH' => date('Y-m-d', strtotime($row['PROJECT_DATE_FINISH']) + (3 * 60 * 60)),//Дата завершение
            'OWNER_ID' => $arr['OWNER_ID'],//Руководитель
            'UF_PROJ_GU' => $arr['UF_PROJ_GU']['VALUE'],//Портфель
            'UF_PROJ_PORTFEL' => $arr['UF_PROJ_PORTFEL'],//Портфель
            'UF_PROJ_KURATOR' => $arr['UF_PROJ_KURATOR'],//Куратор
            'UF_PROJ_PASSPORT' => CFile::GetPath($arr['UF_PROJ_PASSPORT']['VALUE']),//Паспорт проекта
            'UF_PROJ_PLAN' => CFile::GetPath($arr['UF_PROJ_PLAN']['VALUE']),//План проекта
            'UF_PROJ_BUDGET_VIDEL' => $arr['UF_PROJ_BUDGET_VIDEL']['VALUE'],//Выделенный бюджет
            'UF_PROJ_BUDGET_OSVOE' => $arr['UF_PROJ_BUDGET_OSVOE']['VALUE'],//Освоенный бюджет
            'UF_PROJ_BUDGET_DOPOL' => $arr['UF_PROJ_BUDGET_DOPOL']['VALUE'],//Дополнительная потребность
            'UF_PROJ_BUDGET' => $arr['UF_PROJ_BUDGET']['VALUE'],//Бюджет проекта
            'UF_PROJ_MODERATOR' => $arr['UF_PROJ_MODERATOR']['VALUE'],//Модератор
            'UF_PROJ_NA_KANTROLE' => $arr['UF_PROJ_NA_KANTROLE']['VALUE'],//Модератор
            //'STATUS' => $arr['OPENED'] == 'Y' ? 'открытый проект' : 'закрытый проект',//Статус
            'PROJECT_STATUS' => $arr['UF_PROJ_STATUS']['VALUE'],//Статус
            'UF_PROJ_PODRYADCHIK' => $arr['UF_PROJ_PODRYADCHIK']['VALUE'],//Подрядная организация
            'GANT' => 'http://project.bko.gov.kz/workgroups/group/' . $row['ID'] . '/tasks/?F_STATE=sVg0'

        );



    }
    //print_r2($arResult);
    //die();
endif; ///task
///
if ($get['metod'] == 'projects-id'):
    $strSql = "SELECT ID FROM b_sonet_group"; //проекты

    $res = $DB->Query($strSql, false, $err_mess . __LINE__);
    while ($row = $res->Fetch()) {
        $arResult[] = $row['ID'];
    }
endif;

if ($get['metod'] == 'task'):
    $arFilter = array('>GROUP_ID' => 0);
    if(!empty($get['project_id']))
    {
        $arFilter['GROUP_ID']= $get['project_id'];
    }

    $hGTasks = CTasks::GetList(array(), $arFilter);
    while ($row = $hGTasks->Fetch()) {
        if(empty($arResult[$row['GROUP_ID']]))
        {
            $arResult[$row['GROUP_ID']] =
                array(
                    'PROJECT' => $row['GROUP_ID'],
                    'TASKS' => array()
                );
        }

        $arResult[$row['GROUP_ID']]['TASKS'][] = array(
            'TASK_NUMBER' => $row['ID'],
            'NAME' => $row['TITLE'],
            'START_DATE' => $row['START_DATE_PLAN'],
            'END_DATE' => $row['END_DATE_PLAN'],
            'CLOSED_DATE' => $row['CLOSED_DATE'],
            'STATUS' => $row['STATUS'],
        );
    }

    $arResult= array_values($arResult);


endif;
if ($get['metod'] == 'file'):


    define("STOP_STATISTICS", true);
    define("PUBLIC_AJAX_MODE", true);
    define("NO_KEEP_STATISTIC", "Y");
    define("NO_AGENT_STATISTIC", "Y");
    define("DisableEventsCheck", true);

    $siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
    $siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
    if (!empty($siteId) && is_string($siteId)) {
        define('SITE_ID', $siteId);
    }

    if (isset($_GET['action']) && ($_GET['action'] == 'show' || $_GET['action'] == 'downloadFile')) {
        define('BX_SECURITY_SESSION_READONLY', true);
    }


    if (!\Bitrix\Main\Loader::includeModule('disk')) {
        die;
    }

    if (!empty($_GET['document_action']) && !empty($_GET['service'])) {
        if (LocalDocumentController::isLocalService($_GET['service'])) {
            $docController = new LocalDocumentController;
            $docController
                ->setActionName(empty($_GET['primaryAction']) ? $_GET['document_action'] : $_GET['primaryAction'])
                ->exec();
        } else {
            $docController = new \Bitrix\Disk\Uf\DocumentController();
            $docController
                ->setActionName($_GET['document_action'])
                ->setDocumentHandlerName($_GET['service'])
                ->exec();
        }
    }

    /*$oauthToken = $_GET['auth'];
    if($oauthToken && \Bitrix\Main\Loader::includeModule('rest'))
    {
        $authResult = null;
        if(\CRestUtil::checkAuth(
            $oauthToken,
            \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
            $authResult
        ))
        {
            \CRestUtil::makeAuth($authResult);
        }
    }*/

    $ufController = new Bitrix\Disk\Uf\Controller();
    $ufController
        ->setActionName($_GET['action'])
        ->exec();

    $USER->Logout();
    die();
endif;
//$USER->Logout();

header('Content-Type: application/json');
echo json_encode($arResult, JSON_UNESCAPED_UNICODE);

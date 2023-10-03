<?php
namespace classReport;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;

class classReport extends \CBitrixComponent {

    /**
     * @param $idStart
     * @param $limit
     * @param $classID
     * @return array
     */
    private function getStudents($idStart = 0, $limit = 20, $classID = null)
    {
        if($classID)
            $pupils = \CUser::GetList('', '', ['UF_EDU_STRUCTURE' => $classID]);
        else
            $pupils = \CUser::GetList('', '', ['GROUPS_ID' => [9], "<ID" => $limit, ">ID" => $idStart]);

        $allStudents = [];
        $classes = [];
        while ($pupil = $pupils->Fetch())
        {
            $allStudents[$pupil['ID']]['NAME'] = $pupil['NAME'];
            $allStudents[$pupil['ID']]['LAST_NAME'] = $pupil['LAST_NAME'];
            $allStudents[$pupil['ID']]['SECOND_NAME'] = $pupil['SECOND_NAME'];
            if(!empty($pupil['UF_EDU_STRUCTURE'])) {
                if (key_exists($pupil['UF_EDU_STRUCTURE'], $classes)) {
                    $allStudents[$pupil['ID']]['CLASS'] = $classes[$pupil['UF_EDU_STRUCTURE']];
                } else {
                    $classes[$pupil['UF_EDU_STRUCTURE']] = \CIBlockElement::GetByID($pupil['UF_EDU_STRUCTURE'])->Fetch()['NAME'];
                    $allStudents[$pupil['ID']]['CLASS'] = $classes[$pupil['UF_EDU_STRUCTURE']];
                }
            }
        }
        return $allStudents;
    }

    /**
     * @param $title
     * @return void
     */
    private function setTitle($title)
    {
        global $APPLICATION;
        $APPLICATION->SetTitle($title . " " . GetMessage('REPORT'));
    }

    /**
     * @param $activeFrom
     * @param $activeTo
     * @param $class
     * @param $subject
     * @param $teacher
     * @return array|void
     */
    private function getStudentsData ($activeFrom, $activeTo, $class, $subject='', $teacher='')
    {
        $sectionCDB = \CIBlockSection::GetList('', ["UF__EDU_STRUCTURE" => $class, "IBLOCK_ID" => '12']);
        while($section = $sectionCDB->Fetch())
        {
            $sectionID = $section['ID'];
            $sectionName = $section['NAME'];

        }
        $this->setTitle($sectionName);
        $students = $this->getStudents(0, 5, $class);
        if($sectionID)
        {
            $params = [
                'IBLOCK_CODE' => 'school_lessons',
                'SECTION_ID' => $sectionID,
                "ACTICE" => "Y",
                ">DATE_ACTIVE_FROM" => $activeFrom,
                "<DATE_ACTIVE_FROM" => $activeTo
            ];
            if(!empty($subject))
                $params['PROPERTY_SUBJECT'] = $subject;

            if(!empty($teacher))
                $params['PROPERTY_TEACHER'] = $teacher;

            $lessonsCDB = \CIBlockElement::GetList(
                ["DATE_ACTIVE_FROM" => "ASC"],
                $params,
                false,
                false,
                [
                    'PROPERTY_MARKS',
                    'PROPERTY_SUBJECT',
                    "ID",
                    "NAME",
                    "IBLOCK_ID",
                    "DATE_ACTIVE_FROM",
                    "IBLOCK_SECTION_ID"
                ]
            );
            $subjects = [];
            $lessons = [];
            while($lesson = $lessonsCDB->Fetch())
            {
                //getting lessons of class
                if(!empty($lesson['PROPERTY_MARKS_VALUE']))
                {
					$lesson['PROPERTY_MARKS_VALUE'] = is_array(unserialize($lesson['PROPERTY_MARKS_VALUE'])) ? unserialize($lesson['PROPERTY_MARKS_VALUE']) : [];
                    if($students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['SUMMARY'])
                    {
                        if(is_numeric($lesson['PROPERTY_MARKS_VALUE']['MARK']))
                        {
                            if(($lesson['PROPERTY_MARKS_VALUE']['TYPE'] == 'fin1' && (int)date('m') > 6) ||
                                ($lesson['PROPERTY_MARKS_VALUE']['TYPE'] == 'fin2' && (int)date('m') <= 6))
                            {
                                $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['FINAL_MARK'] = (int)$lesson['PROPERTY_MARKS_VALUE']['MARK'];
                            }
                            elseif ($lesson['PROPERTY_MARKS_VALUE']['TYPE'] == 'exam')
                            {
                                $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['EXAM'] = (int)$lesson['PROPERTY_MARKS_VALUE']['MARK'];
                            }
                            else
                            {
                                if($lesson['PROPERTY_MARKS_VALUE']['TYPE'] != 'fin1' && $lesson['PROPERTY_MARKS_VALUE']['TYPE'] != 'fin2') {
                                    $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['ALL'][] = (int)$lesson['PROPERTY_MARKS_VALUE']['MARK'];
                                    $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['SUMMARY'] += (int)$lesson['PROPERTY_MARKS_VALUE']['MARK'];
                                    $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['COUNT']++;
                                }
                            }
                        }
                        else
                        {
                            if($students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['ABSENT']) {
                                $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['ABSENT']++;
                            } else {
                                $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['ABSENT'] = 1;
                            }
                        }
                    }
                    else
                    {
                        if(is_numeric($lesson['PROPERTY_MARKS_VALUE']['MARK']))
                        {
                            if(($lesson['PROPERTY_MARKS_VALUE']['TYPE'] == 'fin1' && (int)date('m') > 6) ||
                                ($lesson['PROPERTY_MARKS_VALUE']['TYPE'] == 'fin2' && (int)date('m') <= 6))
                            {
                                $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['FINAL_MARK'] = (int)$lesson['PROPERTY_MARKS_VALUE']['MARK'];
                            }
                            else
                            {
                                if($lesson['PROPERTY_MARKS_VALUE']['TYPE'] != 'fin1' && $lesson['PROPERTY_MARKS_VALUE']['TYPE'] != 'fin2') {
                                    $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['ALL'][] = (int)$lesson['PROPERTY_MARKS_VALUE']['MARK'];
                                    $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['SUMMARY'] = (int)$lesson['PROPERTY_MARKS_VALUE']['MARK'];
                                    $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['PROPERTY_SUBJECT_VALUE']]['COUNT'] = 1;
                                }
                            }
                        }
                        else
                        {
                            if($students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['ABSENT']) {
                                $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['ABSENT']++;
                            } else {
                                $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['ABSENT'] = 1;
                            }
                        }
                    }

                    if(key_exists($lesson['ID'], $lessons)) {
                        $lessons[$lesson['ID']]['PROPERTY_MARKS_VALUE'][] = $lesson['PROPERTY_MARKS_VALUE'];
                    } else {
                        $lessons[$lesson['ID']] = $lesson;
                    }
                }
                //end of getting class lessons

                //get subjects of class
                if(!empty($subject)){
                    if($subject == $lesson['PROPERTY_SUBJECT_VALUE'])
                        $subjects[$lesson['PROPERTY_SUBJECT_VALUE']] = $lesson['NAME'];
                } elseif(!key_exists($lesson['PROPERTY_SUBJECT_VALUE'], $subjects)){
                    $subjects[$lesson['PROPERTY_SUBJECT_VALUE']] = $lesson['NAME'];
                }
                //end of getting class subjects
            }
            foreach ($students as $k => $stud)
            {
                $names[] = $stud['LAST_NAME'];
				if(!empty($students[$k]["SUBJECTS"]))
                	krsort($students[$k]["SUBJECTS"]);
            }
            array_multisort($names, SORT_ASC, $students);
            krsort($subjects);
            return ["SUBJECTS" => $subjects, "STUDENTS" => $students];
        }
    }

    /**
     * @param $activeFrom
     * @param $activeTo
     * @return array
     */
    private function getAllStudentsData ($activeFrom, $activeTo)
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        if($request->get('STEP')) {
            $students = $this->getStudents($request->get('STEP'));
        } else {
            $students = $this->getStudents($request->get('STEP'));
        }

        $params = [
            'IBLOCK_CODE' => 'school_lessons',
            "ACTICE" => "Y",
            ">DATE_ACTIVE_FROM" => $activeFrom,
            "<DATE_ACTIVE_FROM" => $activeTo
        ];
        if(!empty($subject))
            $params['PROPERTY_SUBJECT'] = $subject;

        if(!empty($teacher))
            $params['PROPERTY_TEACHER'] = $teacher;


        $lessonsCDB = \CIBlockElement::GetList(
            ["DATE_ACTIVE_FROM" => "ASC"],
            $params,
            false,
            false,
            [
                'PROPERTY_MARKS',
                'PROPERTY_SUBJECT',
                "ID",
                "NAME",
                "IBLOCK_ID",
                "DATE_ACTIVE_FROM",
                "IBLOCK_SECTION_ID",
                "ACTIVE_FROM"
            ]
        );
        $subjects = [];
        $lessons = [];
        while($lesson = $lessonsCDB->Fetch())
        {
            //getting lessons of class
            if(!empty($lesson['PROPERTY_MARKS_VALUE']))
            {
				$lesson['PROPERTY_MARKS_VALUE'] = unserialize($lesson['PROPERTY_MARKS_VALUE']);
                if(is_numeric($lesson['PROPERTY_MARKS_VALUE']['MARK']))
                {
                    $students[$lesson['PROPERTY_MARKS_VALUE']['USER']]['SUBJECTS'][$lesson['NAME']][] = [
                        "MARK" => $lesson['PROPERTY_MARKS_VALUE']['MARK'],
                        "TYPE" => $lesson['PROPERTY_MARKS_VALUE']['TYPE'],
                        "DATE" => $lesson["ACTIVE_FROM"]
                    ];
                }


                if(key_exists($lesson['ID'], $lessons)) {
                    $lessons[$lesson['ID']]['PROPERTY_MARKS_VALUE'][] = $lesson['PROPERTY_MARKS_VALUE'];
                } else {
                    $lessons[$lesson['ID']] = $lesson;
                }
            }
            //end of getting class lessons

            //get subjects of class
            if(!key_exists($lesson['PROPERTY_SUBJECT_VALUE'], $subjects))
            {
                $subjects[$lesson['PROPERTY_SUBJECT_VALUE']] = $lesson['NAME'];
            }
            //end of getting class subjects
        }
        foreach ($students as $k => $stud)
        {
			if(is_array($students[$k]["SUBJECTS"]))
            	krsort($students[$k]["SUBJECTS"]);
        }
        krsort($subjects);
        return ["SUBJECTS" => $subjects, "STUDENTS" => $students];
    }

    /**
     * @return false|void
     */
    public function executeComponent ()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        global $DB;
        global $USER;

        $users = \CUser::GetList('', '', ['UF_TEACHER' => $request->get('classID')], ['SELECT' => ["UF_TEACHER", "ID", "NAME"]]);
		$us = [];
        while($u = $users->Fetch()) {
            $us[] = $u['ID'];
        }
        if($this->arParams['SHOW_ALL'] == "N") {
            if (!empty($request->get('classID'))) {
                $class = $request->get('classID');
                $format = $DB->DateFormatToPHP(\CSite::GetDateFormat("SHORT"));
                if($request->get('action') == 'set_dates'){
                    $dateSep = explode("-", $request->get('start_date'));
                    $startDate = $dateSep[2] . '.' . $dateSep[1] . '.' . $dateSep[0] . " 00:00:00";
                    $dateSep = explode("-", $request->get('end_date'));
                    $endDate = $dateSep[2] . '.' . $dateSep[1] . '.' . $dateSep[0] . " 00:00:00";
                } else {
                    if ((int)date('m') < 6) {
                        $startDate = date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL")), mktime(0, 0, 0, 1, 1, date("Y")));
                        $endDate = date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL")), mktime(0, 0, 0, 6, 1, date("Y")));
                    } else {
                        $startDate = date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL")), mktime(0, 0, 0, 7, 1, date("Y")));
                        $endDate = date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL")), mktime(0, 0, 0, 12, 31, date("Y")));
                    }
                }
				if(!in_array($USER->GetID(), $us) && empty($request->get('subjectID'))){
					http_response_code(422);
					return false;
				}

                $result = $this->getStudentsData($startDate, $endDate, $class, $request->get('subjectID'), $request->get('teacher'));
                $this->arResult['STUDENTS'] = $result['STUDENTS'];
                $this->arResult['SUBJECTS'] = $result['SUBJECTS'];
            }
            $this->IncludeComponentTemplate();
        }
        else
        {
            if($request->get('action') == 'set_dates'){
                $dateSep = explode("-", $request->get('start_date'));
                $startDate = $dateSep[2] . '.' . $dateSep[1] . '.' . $dateSep[0] . " 00:00:00";
                $dateSep = explode("-", $request->get('end_date'));
                $endDate = $dateSep[2] . '.' . $dateSep[1] . '.' . $dateSep[0] . " 00:00:00";
            } else {
                if ((int)date('m') < 6) {
                    $startDate = date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL")), mktime(0, 0, 0, 1, 1, date("Y")));
                    $endDate = date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL")), mktime(0, 0, 0, 6, 1, date("Y")));
                } else {
                    $startDate = date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL")), mktime(0, 0, 0, 7, 1, date("Y")));
                    $endDate = date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL")), mktime(0, 0, 0, 12, 31, date("Y")));
                }
            }
            $result = $this->getAllStudentsData($startDate, $endDate);
            $this->arResult['STUDENTS'] = $result['STUDENTS'];
            $this->arResult['SUBJECTS'] = $result['SUBJECTS'];
            $this->IncludeComponentTemplate('all_students');
        }
    }
}
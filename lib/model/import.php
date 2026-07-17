<?php
namespace Drdroid\Keyrights\Model;

use Drdroid\Keyrights\Helper\Crypt;

class Import {

    private $parsedCsvData         = [];
    private $newSections           = [];
    private $existSectionsNames    = [];
    private $existSectionsIds      = [];
    private $existSections         = [];
    private $existPasswords        = [];
    private $errors                = [];

    private $addedPasswords   = 0;
    private $updatedPasswords = 0;
    private $addedSections    = 0;

    private $userModel;
    private $rightManager;

    private $startTime = 0;
    private $currentStep = 0;
    private $currentStep4Index = 0;
    private $maxTime = 15;

    public function __construct() {
        $this->userModel    = new User();
        $this->rightManager = new RightManager();
    }

    public function import($data) {
        $this->startTime = time();
        if (!$this->userModel->isAdmin()) {
            return [
                'result'  => 'error',
                'message' => $this->translate('ONLY_ADMIN_CAN'),
            ];
        }

        $this->parsedCsvData = $data;
        $this->existPasswords = $this->rightManager->getItem(['select' => ['ID', 'SECTION', 'NAME']]);
        $this->existSections  = $this->rightManager->getSection([]);

        for ($i = 0; $i < count($this->existSections); $i++) {
            $this->existSectionsNames[$this->existSections[$i]['NAME']] = $this->existSections[$i];
            $this->existSectionsIds  [$this->existSections[$i]['ID']]   = $this->existSections[$i];
        }

        // find exist sections
        $tmpResult = $this->findExistSections();
        if (is_array($tmpResult)) return $tmpResult;
        $tmpResult = $this->createSectionTree();
        if (is_array($tmpResult)) return $tmpResult;
        $tmpResult = $this->createPasswords();
        if (is_array($tmpResult)) return $tmpResult;

        return $this->getImportResults();
    }

    private function checkTimeLimit() {
        $curTime = time();
        if ($this->startTime + $this->maxTime <= $curTime) {
            $this->saveStepData();
            return ['result' => 'ok', 'data' => 'progress', 'step' => $this->currentStep];
        } else {
            return false;
        }
    }

    private function saveStepData() {
        $_SESSION['keyrights-import'] = [
            'currentStep'           => $this->currentStep,
            'currentStep4Index'     => $this->currentStep4Index,
            'parsedCsvData'         => $this->parsedCsvData,
            'newSections'           => $this->newSections,
            'existSectionsNames'    => $this->existSectionsNames,
            'existSectionsIds'      => $this->existSectionsIds,
            'existSections'         => $this->existSections,
            'existPasswords'        => $this->existPasswords,
            'errors'                => $this->errors,
            'addedPasswords'        => $this->addedPasswords,
            'updatedPasswords'      => $this->updatedPasswords,
            'addedSections'         => $this->addedSections,
        ];
    }

    private function restoreStepData() {
        $data = isset($_SESSION['keyrights-import']) ? $_SESSION['keyrights-import'] : null;
        if (isset($data) && is_array($data)) {
            $this->currentStep           = $data['currentStep'];
            $this->currentStep4Index     = $data['currentStep4Index'];
            $this->parsedCsvData         = $data['parsedCsvData'];
            $this->newSections           = $data['newSections'];
            $this->existSectionsNames    = $data['existSectionsNames'];
            $this->existSectionsIds      = $data['existSectionsIds'];
            $this->existSections         = $data['existSections'];
            $this->existPasswords        = $data['existPasswords'];
            $this->errors                = $data['errors'];
            $this->addedPasswords        = $data['addedPasswords'];
            $this->updatedPasswords      = $data['updatedPasswords'];
            $this->addedSections         = $data['addedSections'];

            return true;
        }
        return false;
    }

    public function continueImport() {
        if (!$this->restoreStepData()) {
            return ['result' => 'error', 'error' => $this->translate('IMPORT_PROCESS_FAIL')];
        }

        $this->startTime = time();

        if ($this->currentStep == 2) {
            $tmpResult = $this->refindExistSections();
            if (is_array($tmpResult)) return $tmpResult;
            $tmpResult = $this->createSectionTree();
            if (is_array($tmpResult)) return $tmpResult;
            $tmpResult = $this->createPasswords();
            if (is_array($tmpResult)) return $tmpResult;

            return $this->getImportResults();

        } elseif ($this->currentStep == 3) {
            $tmpResult = $this->createSectionTree();
            if (is_array($tmpResult)) return $tmpResult;
            $tmpResult = $this->createPasswords();
            if (is_array($tmpResult)) return $tmpResult;

            return $this->getImportResults();

        } elseif ($this->currentStep == 4) {
            $tmpResult = $this->createPasswords();
            if (is_array($tmpResult)) return $tmpResult;

            return $this->getImportResults();

        } else {
            return $this->import($this->parsedCsvData);
        }
    }

    private function findExistSections() {
        $this->currentStep = 1;
        $this->newSections = [];

        for ($i = 0; $i < count($this->parsedCsvData); $i++) {
            if ($this->parsedCsvData[$i]['SECTION_ID']) {
                continue;
            }

            $foundSectionId = $this->findSectionId($this->parsedCsvData[$i]['SECTION'], $this->parsedCsvData[$i]['PARENT_SECTION']);
            $this->parsedCsvData[$i]['SECTION_ID'] = $foundSectionId;

            if (!$foundSectionId) {
                $foundNewSection = false;
                for ($j = 0; $j < count($this->newSections); $j++) {
                    if (($this->newSections[$j]['NAME'] == $this->parsedCsvData[$i]['SECTION']) && ($this->newSections[$j]['PARENT_NAME'] == $this->parsedCsvData[$i]['PARENT_SECTION'])) {
                        $foundNewSection = true;
                        break;
                    }
                }

                if (!$foundNewSection) {
                    $this->newSections[] = [
                        'NAME'        => $this->parsedCsvData[$i]['SECTION'],
                        'PARENT_NAME' => $this->parsedCsvData[$i]['PARENT_SECTION']
                    ];
                }
            }
        }

        return true;
    }

    private function refindExistSections() {
        $this->currentStep = 2;

        for ($i = 0; $i < count($this->parsedCsvData); $i++) {
            if ($this->parsedCsvData[$i]['SECTION_ID']) {
                continue;
            }

            $foundSectionId = $this->findSectionId($this->parsedCsvData[$i]['SECTION'], $this->parsedCsvData[$i]['PARENT_SECTION']);
            $this->parsedCsvData[$i]['SECTION_ID'] = $foundSectionId;
        }

        return true;
    }

    private function createSectionTree() {
        $this->currentStep = 3;

        do {
            $countActions = 0;

            for ($i = 0; $i < count($this->newSections); $i++) {
                if (isset($this->newSections[$i]['ID']) && $this->newSections[$i]['ID']) continue;

                if (empty($this->newSections[$i]['PARENT_NAME'])) {
                    $countActions++;

                    $fields = [
                        'NAME' => $this->newSections[$i]['NAME'],
                        'DESCRIPTION' => ''
                    ];

                    $newSectionId = $this->rightManager->addSection($fields);

                    $this->newSections[$i] = array_merge(['ID' => $newSectionId], $fields);

                    $this->existSections[] = $this->newSections[$i];
                    $this->existSectionsIds[$this->newSections[$i]['ID']] = $this->newSections[$i];
                    $this->existSectionsNames[$this->newSections[$i]['NAME']] = $this->newSections[$i];

                } elseif (isset($this->existSectionsNames[$this->newSections[$i]['PARENT_NAME']])) {
                    $countActions++;
                    $fields = [
                        'NAME' => $this->newSections[$i]['NAME'],
                        'SECTION' => $this->existSectionsNames[$this->newSections[$i]['PARENT_NAME']]['ID'],
                        'IBLOCK_SECTION_ID' => $this->existSectionsNames[$this->newSections[$i]['PARENT_NAME']]['ID'],
                        'DESCRIPTION' => ''
                    ];
                    $newSectionId = $this->rightManager->addSection($fields);
                    $this->newSections[$i] = array_merge(['ID' => $newSectionId], $fields);

                    $this->existSections[] = $this->newSections[$i];
                    $this->existSectionsIds[$this->newSections[$i]['ID']] = $this->newSections[$i];
                    $this->existSectionsNames[$this->newSections[$i]['NAME']] = $this->newSections[$i];
                } else {
                    $countActions++;

                    $fields = [
                        'NAME' => $this->newSections[$i]['PARENT_NAME'],
                        'DESCRIPTION' => ''
                    ];

                    $parentSectionId = $this->rightManager->addSection($fields);

                    $newParentSection = ['ID' => $parentSectionId, 'NAME' => $this->newSections[$i]['PARENT_NAME']];

                    $this->existSections[] = $newParentSection;
                    $this->existSectionsIds[$parentSectionId] = $newParentSection;
                    $this->existSectionsNames[$this->newSections[$i]['PARENT_NAME']] = $newParentSection;

                    $countActions++;

                    $fields = [
                        'NAME' => $this->newSections[$i]['NAME'],
                        'SECTION' => $this->existSectionsNames[$this->newSections[$i]['PARENT_NAME']]['ID'],
                        'IBLOCK_SECTION_ID' => $this->existSectionsNames[$this->newSections[$i]['PARENT_NAME']]['ID'],
                        'DESCRIPTION' => ''
                    ];

                    $newSectionId = $this->rightManager->addSection($fields);

                    $this->newSections[$i] = array_merge(['ID' => $newSectionId], $fields);

                    $this->existSections[] = $this->newSections[$i];
                    $this->existSectionsIds[$this->newSections[$i]['ID']] = $this->newSections[$i];
                    $this->existSectionsNames[$this->newSections[$i]['NAME']] = $this->newSections[$i];
                }
            }

            if ($countActions) {
                $this->refindExistSections();
            }

            $tmpResult = $this->checkTimeLimit();
            if (is_array($tmpResult)) {
                $tmpResult['s'] = $this->newSections;
                $tmpResult['ca'] = $countActions;
                return $tmpResult;
            }

        } while ($countActions > 0);

        return true;
    }

    private function createPasswords() {
        $this->currentStep = 4;

        $this->existSections = [];
        $this->existSectionsIds = [];
        $this->existSectionsNames = [];

        for ($i = $this->currentStep4Index; $i < count($this->parsedCsvData); $i++) {
            if (isset($this->parsedCsvData[$i]['IS_PROCESSED']) && $this->parsedCsvData[$i]['IS_PROCESSED']) continue;

            $this->parsedCsvData[$i]['IS_PROCESSED'] = true;
            $pass = $this->parsedCsvData[$i];
            $sectionId = $pass['SECTION_ID'];

            if (!$sectionId) {
                $this->errors[] = 'For password ' . $pass['NAME'] . ' in section ' . $pass['SECTION'] . ' parent section ID not found';
                continue;
            }

            $foundExist = false;
            for ($j = 0; $j < count($this->existPasswords); $j++) {
                if (($this->existPasswords[$j]['SECTION'] == $sectionId) && ($this->existPasswords[$j]['NAME'] == $pass['NAME'])) {
                    $foundExist = true;
                    $this->updatePassword(
                        $this->existPasswords[$j]['ID'],
                        $this->existPasswords[$j]['NAME'],
                        $this->existPasswords[$j]['SECTION'],
                        $pass['CRYPTED']
                    );
                    break;
                }
            }

            if (!$foundExist) {
                $this->addPassword($pass['NAME'], $sectionId, $pass['CRYPTED']);
            }

            $this->parsedCsvData[$i] = ['IS_PROCESSED' => true];
            $this->currentStep4Index = $i + 1;
            $tmpResult = $this->checkTimeLimit();
            if (is_array($tmpResult)) return $tmpResult;
        }

        return true;
    }

    public function getImportResults() {
        unset($_SESSION['keyrights-import']);
        return [
            'result' => 'ok',
            'data'   => [
                'errors' => $this->errors,
                'stat' => [
                    'addedPasswords' => $this->addedPasswords,
                    'updatedPasswords' => $this->updatedPasswords,
                    'addedSections' => $this->addedSections
                ]
            ]
        ];
    }

    private function findSectionId($sectionName, $parentName) {
        for ($i = 0; $i < count($this->existSections); $i++) {
            if ($this->existSections[$i]['NAME'] == $sectionName) {
                if (!empty($parentName)) {
                    $parentId = $this->existSections[$i]['SECTION'];

                    if (isset($this->existSectionsIds[$parentId]) && $this->existSectionsIds[$parentId]['NAME'] == $parentName) {
                        return $this->existSections[$i]['ID'];
                    }
                } else {
                    if (!$this->existSections[$i]['SECTION']) {
                        return $this->existSections[$i]['ID'];
                    }
                }
            }
        }

        return false;
    }

    private function addPassword($name, $section, $crypted) {
        $this->addedPasswords++;
        $params = [
            'NAME'      => $name,
            'SECTION'   => $section,
            'PROPERTY_VALUES' => ['CRYPTED' => $crypted]
        ];

        return $this->rightManager->addItem($params);
    }

    private function updatePassword($id, $name, $section, $crypted) {
        $this->updatedPasswords++;
        $params = [
            'ID' => $id,
            'NAME' => $name,
            'SECTION' => $section,
            'PROPERTY_VALUES' => ['CRYPTED' => $crypted]
        ];

        return $this->rightManager->updateItem($params);
    }

    private function translate($key) {
        static $messages = null;
        if ($messages === null) {
            $messages = class_exists('CKeyrights') ? \CKeyrights::getTranslations() : [];
        }
        return isset($messages[$key]) ? $messages[$key] : $key;
    }
}

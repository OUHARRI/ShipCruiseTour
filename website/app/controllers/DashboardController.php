<?php

class DashboardController
{
    protected mixed $PreviewUrl;

    public function __construct()
    {
        redirect::sessionAdmin();
        $this->PreviewUrl = $_SERVER['HTTP_REFERER'] ?? url();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function index(): void
    {
        $port = new Port();
        $croisiere = new Croisiere();
        $reservation = new Reservation();

//        $Rom = new Rom();
//        $Navire = new Navire();
//        $RomType = new TypeRom();
//        $countries = new countries();
//        $itinery = new CruiseItinery();
//
//
//        $data['Navire'] = $Navire->getAllNavire();
//        $data['Port'] = $Port->getAllPort();
//        $data['RomType'] = $RomType->getAllTypeRom();
//        $data['croisiere'] = $croisiere->getAllCroisiere();
//        $data['Rom'] = $Rom->getAllRom();
//
//        $i = 0;
//        foreach ($data['Rom'] as $R) {
//            $data['Rom'][$i]['typeRom'] = $RomType->getRow($R['typeRom'])['libelle'];
//            $data['Rom'][$i]['navire'] = $Navire->getRow($R['navire'])['libelle'];
//            $i++;
//        }
//        $i = 0;
//        foreach ($data['croisiere'] as $c) {
//            $data['croisiere'][$i]['navire'] = $Navire->getRow($c['navire'])['libelle'];
//            $data['croisiere'][$i]['itinery'] = $itinery->getRow($c['id'], 'croisiére');
//            $data['croisiere'][$i]['departmentPort'] = $Port->getRow($c['departmentPort'])['name'];
//            $j = 0;
//            foreach ($data['croisiere'][$i]['itinery'] as $it) {
//                $data['croisiere'][$i]['itinery'][$j] = $Port->getRow((int)implode('', $it))['name'];
//                $j++;
//            }
//            $i++;
//        }
//        $i = 0;
//        foreach ($data['Port'] as $P) {
//            $data['Port'][$i]['countrie'] = $countries->getRow($P['countrie'])['name'];
//            $i++;
//        }

        $data['statistic']['TotalCruises'] = $croisiere->getTotal();
        $data['statistic']['TotalPort'] = $port->getTotal();
        $data['statistic']['avr'] = round($reservation->getAvgStatistic(date("m"), date("Y")), 2);
        if ((date("m") - 1) == 0) {
            $d = 12;
            $m = date("Y") - 1;
        } else {
            $d = date("d");
            $m = date("Y");
        }
        $tmp = $reservation->getAvgStatistic($d, $m) ?? 0;
        $data['years'] = range(2018, strftime("%Y", time()));
        $data['statistic']['avrP'] = round(($data['statistic']['avr'] - $tmp) * 100, 2);
        $data['statistic']['Res'] = $reservation->getStatistic(date("Y-m-d"));
        $tmp = $reservation->getStatistic(date("Y-m") . '-' . (date("d") - 1));
        $data['statistic']['%'] = round(($data['statistic']['Res'] - $tmp) * 100, 2);

        View::load('dashboard/dash', $data);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function statistic(): void
    {
        $croisiere = new Croisiere();
        $data['statistic'] = $croisiere->getStatisticCroisiere($_POST['value'] ?? date('Y'));

        for ($j = 1; $j <= 12; $j++) {
            $flag = true;
            foreach ($data['statistic'] as $iValue) {
                if ($iValue["MONTH"] === $j) {
                    $flag = false;
                }
            }
            if ($flag) {
                $data['statistic'][] = array(
                    "COUNT" => 0,
                    "MONTH" => $j
                );
            }
        }

        header('Content-type: application/json');
        echo json_encode(array_values($data['statistic']), JSON_THROW_ON_ERROR);
    }

    public function pages(): void
    {
        View::load('dashboard/pages');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function cruise(): void
    {
        $i = 0;
        $Port = new Port();
        $Navire = new Navire();
        $croisiere = new Croisiere();
        $itinery = new CruiseItinery();
        $data['croisiere'] = $croisiere->getAllCroisiere();

        foreach ($data['croisiere'] as $c) {
            $statisticTmp = $croisiere->getCapacity($c['id'])[0] ?? false;
            $data['croisiere'][$i]['navire'] = $Navire->getRow($c['navire'])['libelle'];
            $data['croisiere'][$i]['itinery'] = $itinery->getRow($c['id'], 'croisiére');
            $data['croisiere'][$i]['departmentPort'] = $Port->getRow($c['departmentPort'])['name'];
            $data['croisiere'][$i]['statistic'] = $statisticTmp ? round($statisticTmp['reserved'] / $statisticTmp['capacity'] * 100, 2) : 0;
            $j = 0;
            foreach ($data['croisiere'][$i]['itinery'] as $it) {
                $data['croisiere'][$i]['itinery'][$j] = $Port->getRow((int)implode('', $it))['name'];
                $j++;
            }
            $i++;
        }

        View::load('dashboard/cruises', $data);
    }

    /**
     * @throws Exception
     */
    public function addCruises(): void
    {
        $cruises = new Croisiere();
        $cruiseItinery = new CruiseItinery();
        $matricule = (int)($cruises->getLastId() + 1);
//        $cruises->startTransaction();


        if (isset($_POST['matricule'])) {
            extract($_POST);
            $data = array(
                'id' => $matricule,
                'desc' => $desc,
                'navire' => $navire,
                'numberOfNight' => $nbrnuit,
                'name' => $cruisesName,
                'departmentPort' => $departport,
                'DateOfDeparture' => $DateOfDeparture,
                'TimeOfDeparture' => $TimeOfDeparture,
                'img' => file_get_contents($_FILES['image']['tmp_name']),
            );
            if ($cruises->insert($data)) {

                $count = 1;
//                $flag= true;
//                $cruiseItinery->startTransaction();
                while (isset(${"cruiseitinery" . $count}) && !empty(${"cruiseitinery" . $count})) {
                    $data = array(
                        'port' => ${"cruiseitinery" . $count},
                        'croisiére' => $matricule,
                    );
                    if (!$cruiseItinery->insert($data)) {
//                        $flag = false;
                        $data['error'] .= " Error adding itinery (PROT" . $count . ")";
                    }
                    $count++;
                }
//                if($flag){
//                    $cruises->commit();
//                    $cruiseItinery->commit();
//                }else{
//                    $cruises->rollback();
//                    $cruiseItinery->rollback();
//                }
                notif::add('croisiére added successfully');
            } else {
                notif::add('Error adding croisiére', 'error');
            }

            unset($_POST);
            header("Refresh:0");
        }

        $Navire = new Navire();
        $Port = new port();
        $data['matricule'] = $matricule;
        $data['Navire'] = $Navire->getAllNavire();
        $data['Port'] = $Port->getAllPort();

        View::load('dashboard/addCruise', $data);
    }

    /**
     * @throws Exception
     */
    public function deletCruises($id): void
    {
        $db = new Croisiere();
        if ($db->delete($id)) {
            notif::add('cruises deleted successfully');
            header('location: ' . $this->PreviewUrl);
        } else {
            notif::add('Error deleting cruise', 'error');
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function Navire(): void
    {
        $Navire = new Navire();
        $data['Navire'] = $Navire->getAllNavire();
        View::load('dashboard/Navire', $data);
    }

    /**
     * @throws Exception
     */
    public function addNavire(): void
    {
        $data = [];
        if (isset($_POST['navirName'])) {
            extract($_POST);
            $data = array(
                'libelle' => $navirName,
                'numberOfRom' => $nbrrom,
                'numberOfPlaces' => $nbrprsn,
                'img' => file_get_contents($_FILES['image']['tmp_name']),
            );
            $db = new Navire();
            if ($db->insert($data)) {
                notif::add('ship added successfully');
            } else {
                notif::add('Error adding ship', 'error');
            }
        }
        View::load('dashboard/addNavire', $data);
    }

    /**
     * @throws Exception
     */
    public function deletNavire($id): void
    {
        $db = new Navire();
        if ($db->delete($id)) {
            notif::add('ship deleted successfully');
            header('location: ' . $this->PreviewUrl);
        } else {
            notif::add('Error deleting ship', 'error');
        }
    }

    /**
     * @throws Exception
     */
    public function Port(): void
    {
        $i = 0;
        $Port = new Port();
        $countries = new countries();
        $data['Port'] = $Port->getAllPort();
        foreach ($data['Port'] as $P) {
            $data['Port'][$i]['countrie'] = $countries->getRow($P['countrie'])['name'];
            $i++;
        }
        View::load('dashboard/Port', $data);
    }

    /**
     * @throws Exception
     */
    public function addPort(): void
    {
        if (isset($_POST['portName'])) {
            extract($_POST);
            $data = array(
                'name' => $portName,
                'countrie' => $countrie,
                'matricule' => $matricule,
                'city' => $city,
                'img' => file_get_contents($_FILES['image']['tmp_name'])
            );
            $db = new Port();
            if ($db->insert($data)) {
                notif::add('port added successfully');
                $data['port'] = $db->getAllPort();
            } else {
                notif::add('Error adding this port', 'error');
            }
        }

        $countrie = new countries();
        $data['countrie'] = $countrie->getAllCountries();
        View::load('dashboard/addPort', $data);
    }

    /**
     * @throws Exception
     */
    public function deletPort($id): void
    {
        $db = new Port();
        if ($db->delete($id)) {
            notif::add('Port deleted successfully');
            header('location: ' . $this->PreviewUrl);
        } else {
            notif::add('Error deleting port', 'error');
        }
    }


    /**
     * @return void
     * @throws Exception
     */
    public function Rom(): void
    {
        $Rom = new Rom();
        $Navire = new Navire();
        $RomType = new TypeRom();
        $data['Rom'] = $Rom->getAllRom();
        $i = 0;
        foreach ($data['Rom'] as $R) {
            $data['Rom'][$i]['typeRom'] = $RomType->getRow($R['typeRom'])['libelle'];
            $data['Rom'][$i]['navire'] = $Navire->getRow($R['navire'])['libelle'];
            $i++;
        }
        View::load('dashboard/Rom', $data);
    }

    /**
     * @throws Exception
     */
    public function addRom(): void
    {
        if (isset($_POST['RomType'])) {
            extract($_POST);
            if (isset($RomType)) {
                $data = array(
                    'typeRom' => $RomType,
                    'navire' => $Navire,
                    'numberOfRom' => $nbrRom,
                    'capacity' => $capacity,
                );
                $db = new Rom();
                if ($db->insert($data)) {
                    notif::add('Rom added successfully');
                } else {
                    notif::add('Error adding this rom', 'error');
                }
            } else {
                notif::add('Please chose rom Type !', 'error');
            }
        }

        $RomType = new TypeRom();
        $Navire = new Navire();
        $data['RomType'] = $RomType->getAllTypeRom();
        $data['Navire'] = $Navire->getAllNavire();

        View::load('dashboard/addRom', $data);
    }

    /**
     * @throws Exception
     */
    public function deletRom($id): void
    {
        $db = new Rom();
        if ($db->delete($id)) {
            notif::add('Rom deleted successfully');
            header('location: ' . $this->PreviewUrl);
        } else {
            notif::add('Error deleting rom', 'error');
        }
    }

    /**
     * @throws JsonException
     */
    public function getMaxRomType($id): void
    {
        $RomType = new TypeRom();
        header('Content-type: application/json');
        echo json_encode($RomType->getMaxRomType($id), JSON_THROW_ON_ERROR);
    }

    /**
     * @throws Exception
     */
    public function TypeRom(): void
    {
        $RomType = new TypeRom();
        $data['RomType'] = $RomType->getAllTypeRom();
        View::load('dashboard/TypeRom', $data);
    }

    /**
     * @throws Exception
     */
    public function addTypeRom(): void
    {
        $data = [];
        if (isset($_POST['romName'])) {
            extract($_POST);
            $data = array(
                'libelle' => $romName,
                'price' => $priceRom,
                'min' => $minprsn,
                'max' => $maxprsn,
                'img' => file_get_contents($_FILES['image']['tmp_name']),
            );
            $db = new TypeRom();
            if ($db->insert($data)) {
                notif::add('type Rom added successfully');
            } else {
                notif::add('Error adding this type rom', 'error');
            }
        }
        View::load('dashboard/addtyperom', $data);
    }

    /**
     * @throws Exception
     */
    public function deletTypeRom($id): void
    {
        $db = new TypeRom();
        if ($db->delete($id)) {
            notif::add('Type Rom deleted successfully');
            header('location: ' . $this->PreviewUrl);
        } else {
            notif::add('Error deleting type rom', 'error');
        }
    }

    /**
     * @throws Exception
     */
    public function user(): void
    {
        $user = new users();
        $data['users'] = $user->getAll();
        View::load('dashboard/users', $data);
    }

    /**
     * @throws Exception
     */
    public function deletUser($id): void
    {
        $user = new users();
        if ($user->delete($id)) {
            notif::add('user deleted successfully');
            header('location: ' . $this->PreviewUrl);
        } else {
            notif::add('Error deleting user', 'error');
        }
    }

    /**
     * @throws Exception
     */
    public function editUsers($id): void
    {
        $users = new users();
        if ($users->getRow($id)) {
            if ($users->getRow($id)['is_admin']) {
                if ($users->setClient($id)) {
                    notif::add('user edited successfully');
                    if ($id === $_SESSION['user']['id_u']) {
                        $log = new loginController();
                        $log->deconnect();
                    }
                } else {
                    $flag = false;
                    notif::add('error in edited user', 'error');
                }
            } else {
                if ($users->setAdmin($id)) {
                    notif::add('user edited successfully');
                } else {
                    $flag = false;
                    notif::add('error in edited user', 'error');
                }
            }
        } else {
            redirect('dashboard/P404');
            exit();
        }
        redirect('dashboard/user');
    }

}
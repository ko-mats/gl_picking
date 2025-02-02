<?php

error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR);
ini_set('display_errors', 1);

require __DIR__ . "/conf.php";
require_once(dirname(__FILE__) . "/class/init_val.php");
require(dirname(__FILE__) . "/class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

session_start();

$five_back = "";

// セッションIDが一致しない場合はログインページにリダイレクト
if (!isset($_SESSION["sid"])) {
    header("Location: index.php");
    exit;
} else {
    $session_id = $_SESSION['sid'];
}

// session判定
if (empty($session_id)) {
    // *** セッションIDがないので、リダイレクト
    header("Location: $top_url");
} else {

    // 倉庫名　取得
    if (isset($_SESSION['soko_name'])) {
        $get_souko_name = $_SESSION['soko_name'];
        $_SESSION['soko_name'] = $get_souko_name;
        dprint("01:::" . $_SESSION['soko_name']);
    } else {
        $souko_name = $_SESSION['soko_name'];
        dprint("02:::" . $_SESSION['soko_name']);
    }

    if (isset($_GET['day'])) {
        $select_day = $_GET['day'];
    }

    if (isset($_GET['unsou_name'])) {
        $get_unsou_name = $_GET['unsou_name'];
        $_SESSION['unsou_name'] = $get_unsou_name;
    }

    if (isset($_GET['unsou_code'])) {
        $select_unsou_code = $_GET['unsou_code'];
    }

    // 2024/06/05
    if (isset($_GET['selectedToki_Code'])) {
        $selectedToki_Code = $_GET['selectedToki_Code'];
    }

    if (isset($_GET['fukusuu_unsouo_num'])) {
        $fukusuu_unsouo_num = $_GET['fukusuu_unsouo_num'];
    }

    if (isset($_GET['fukusuu_select'])) {
        $fukusuu_select = $_GET['fukusuu_select'];
    }

    if (isset($_GET['fukusuu_select_val'])) {
        $fukusuu_select_val = $_GET['fukusuu_select_val'];
    }

    $sql_multiple_cut = "";

    dprintBR("現在のパターン数:::");
    dprintBR($_SESSION['forth_pattern']);
    dprintBR($_SESSION['fukusuu_select']);



    // 2024/06/07 修正
    // ==========================================================
    // ================= 通常処理　（運送単数） =================
    // ==========================================================


    // ********* four.php 通常 => five.php 「戻る」 ルート OK *********

    // isset($_GET['third_default_sql']) && $_GET['third_default_sql'] == "third_default_sql_VAL" 「third.php からの 判別」
    // isset($_SESSION['third_default_sql']) && $_SESSION['third_default_sql'] == "third_default_sql_VAL"  「five.php からの戻り 判別」
    // ========= 備考・特記　なし
    if (
        isset($_GET['forth_pattern']) && $_GET['forth_pattern'] === 'one' ||
        isset($_SESSION['forth_pattern']) && $_SESSION['forth_pattern'] === 'one'/* &&
        !isset($_GET['sort_areab']) && !isset($_GET['sort_areac']) ||
        isset($_GET['sort_key']) && isset($_GET['sort_areaa']) ||
        isset($_GET["show_all_flg"]) && $_GET["show_all_flg"] == 0 ||
        isset($_GET["all_flg"]) && $_GET["all_flg"] == 0
        */
    ) {

        dprint("通常処理 （運送単数）" . "<br>");

        // === ********* third.php から、　four.php へ持ってきた 状態判別パラメータ *********
        if (isset($_GET['forth_pattern']) && !empty($_GET['forth_pattern'])) {
            $forth_pattern = $_GET['forth_pattern'];
            $_SESSION['forth_pattern'] = $forth_pattern;

            dprint("通常パラメータ" . $forth_pattern . "<br>");
        }

        if (isset($_GET['day'])) {
            $select_day = $_GET['day'];
        }

        if (isset($_GET['souko'])) {
            $select_souko_code = $_GET['souko'];
        }

        if (isset($_GET['get_souko_name'])) {
            $get_souko_name = $_GET['get_souko_name'];
        }

        if (isset($_GET['unsou_code'])) {
            $select_unsou_code = $_GET['unsou_code'];
        }

        // === five.php から戻ってきた用
        if (isset($_GET['Unsou_code'])) {
            $select_unsou_code = $_GET['Unsou_code'];
        }

        // 並替ボタン選択時 2024/06/05
        $sortKey = "";
        if (isset($_GET['sort_key'])) {
            $sortKey = $_GET['sort_key'];
        }
        // 全表示フラグ確認 2024/06/07
        if (isset($_GET['show_all_flg'])) {
            $show_all_flg = $_GET['show_all_flg'];
        }

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // SQL 修正 24_0522 最新

        // === SQL 受け渡し
        if (isset($_GET['default_root_sql_back'])) {
            $sql = $_GET['default_root_sql_back'];
            $_SESSION['one_now_sql_back_kanryou'] = $sql;
        } else {


            $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
                      ,RZ.棚番
                      ,SH.梱包入数
                      ,SUM(SL.数量) AS 数量    
                      ,SUM(PK.ピッキング数量) AS ピッキング数量
                      ,PK.処理Ｆ
                      ,SH.ＪＡＮ
                      ,SK.特記事項
                 FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                      ,RZMF RZ
                      ,HTPK PK
                 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                   AND SK.伝票行番号 = SL.伝票行番号
                   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                   AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
                   AND SL.伝票番号   = PK.伝票番号(+)
                   AND SL.伝票行番号 = PK.伝票行番号(+)
                   AND SL.伝票行枝番 = PK.伝票行枝番(+)
                   AND SL.倉庫Ｃ = SO.倉庫Ｃ
                   AND SL.出荷元 = SM.出荷元Ｃ(+)
                   AND SJ.運送Ｃ = US.運送Ｃ
                   AND SL.商品Ｃ = SH.商品Ｃ
                   AND SL.倉庫Ｃ = RZ.倉庫Ｃ
                   AND SL.商品Ｃ = RZ.商品Ｃ
                   AND SJ.出荷日 = :SELECT_DATE  
                   AND SL.倉庫Ｃ = :SELECT_SOUKO
                   AND SJ.運送Ｃ = :SELECT_UNSOU
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02 ";
        }

        //2024/06/07 全表示
        if (!isset($_GET["show_all_flg"])) {    // show_all_flgがセットされていない場合
            $sql .= "AND NVL(PK.処理Ｆ,0) <> 9 ";
        } else {                                // show_all_flgがセットされている場合
            $all_flg = 0;
        }

        $sql .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項 ";

        // 2024/06/05
        switch ($sortKey) {
            case 'location_note':
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'num_note':
                $sql .= "ORDER BY 数量,SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'tokki_note':
                $sql .= "ORDER BY SK.特記事項,SM.出荷元名,SL.倉庫Ｃ,SJ.運送Ｃ,SL.商品Ｃ,SL.出荷元 ";
                break;

            case 'bikou_note':
                $sql .= "ORDER BY SM.出荷元名,SK.特記事項,SL.倉庫Ｃ,SJ.運送Ｃ,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            default:
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;
        }

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
        oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);
        oci_bind_by_name($stid, ":SELECT_SOUKO_02", $select_souko_code);


        // === SQL をセッションへ格納
        $_SESSION['four_five_default_SQL'] = $sql;
        //echo $sql;
        oci_execute($stid);

        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $tokki_zikou    = $row['特記事項'];


            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                'Unsou_code' => $Unsou_code,                // SJ.運送Ｃ
                'Unsou_name' => $Unsou_name,                // US.運送略称
                'shipping_moto' => $shipping_moto,          // SL.出荷元
                'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                'shouhin_JAN' => $shouhin_JAN,              // JANコード
                'tokki_zikou' => $tokki_zikou,                // 特記事項
                'four_status' => 'default_root'               // five.php への遷移状態
            );
        }

        oci_free_statement($stid);
        oci_close($conn);


        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $get_souko_name = $_SESSION['soko_name'];
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }

        // 2024/06/07 修正
        // ================================================================================
        // ====================  単数 運送コード 特記・備考あり  ============================
        // ================================================================================

        // === three.php からの 状態判別 用　パラメータ selectedToki_Code
    } else if ((isset($_GET['forth_pattern']) && $_GET['forth_pattern'] === "two")
        || isset($_SESSION['forth_pattern']) && $_SESSION['forth_pattern'] === "two"
//        || isset($_GET['five_back_button']) && !isset($_GET['sort_areaa']) && !isset($_GET['sort_areac'])
//        || isset($_GET['sort_key']) && isset($_GET['sort_areab']) || isset($_GET["show_all_flg"]) && $_GET["show_all_flg"] == 1
//        || isset($_GET["all_flg"]) && $_GET["all_flg"] == 1
    ) {

        dprint("単数 運送コード 特記・備考あり" . "<br>");

        //      dprint("単数 運送コード 特記・備考あり");
        // ============= 運送便（単数） & 備考・特記あり ================


        // === third.php の 判別用パラメータをセッションへ格納

        // ==========================================
        // === four.php から　five.php へ戻ってきた場合
        // ==========================================

        // === GET パラメータ取得
        $select_unsou_code = $_GET['unsou_code'];
        if (isset($_GET['unsou_name'])) {
            $get_unsou_name = $_GET['unsou_name'];
        }
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];

        $sql = $_SESSION['sql_one_option'];
        $_SESSION['sql_one_option'] = $sql;


        if (isset($_GET['get_souko_name'])) {
            $get_souko_name = $_GET['get_souko_name'];
        }

        // ========================================
        // === third.php => から　four.php 　判別の値
        // ========================================
        if (isset($_GET['forth_pattern']) && $_GET['forth_pattern'] == 'two') {
            $_SESSION['forth_pattern'] = $_GET['forth_pattern'];
        }


        // 運送便　& 備考, 特記事項　文字列   :コロン区切り
        $selectedToki_Code = $_GET['selectedToki_Code'];

        //dprint($selectedToki_Code . "<br><br>");

        // === 特記、備考 部分を保持
        if (isset($_SESSION['selectedToki_Code']) && !empty($_SESSION['selectedToki_Code'])) {
            $selectedToki_Code = $_SESSION['selectedToki_Code'];
        } else {
            $_SESSION['selectedToki_Code'] = $selectedToki_Code;
        }
        //echo 'これが' . $selectedToki_Code . '<br>';
        //echo 'これも' . $_SESSION['selectedToki_Code']  . '<br>';


        // index , 0 => 運送名 , 1 => 運送コード , 2 => 出荷元 , 3 => 特記事項
        $arr_SQL = explode(":", $selectedToki_Code);

        /* print_r($arr_SQL);
        print($arr_SQL[0] . "<br>");
        print($arr_SQL[1] . "<br>");
        print($arr_SQL[2] . "<br>");
        print($arr_SQL[3] . "<br>");
 */
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        if (isset($_GET['unsou_name'])) {
            $get_unsou_name = $_GET['unsou_name'];
        }

        // 可変部分の条件を生成
        $conditions = [];

        $conditionSet[0] = "SJ.運送Ｃ = '{$arr_SQL[1]}'";

        if ($arr_SQL[2] !== '-') {
            $conditionSet[1] = "SL.出荷元 = '{$arr_SQL[2]}'";
        } else {
            $conditionSet[1] = "SL.出荷元 IS NULL";
        }

        if ($arr_SQL[3] !== '---') {
            $conditionSet[2] = "SK.特記事項 = '{$arr_SQL[3]}'";
        } else {
            $conditionSet[2] = "SK.特記事項 IS NULL";
        }

        // $conditions[] = '(' . implode(' AND ', $conditionSet) . ')';

        $conditions[] = '(' . $conditionSet[0] . ')' . ' AND ' . '(' . $conditionSet[1] . ')' . ' AND ' . '(' . $conditionSet[2] . ')';

        // 並替ボタン選択時 2024/06/05
        $sortKey = "";
        if (isset($_GET['sort_key'])) {
            $sortKey = $_GET['sort_key'];
        }

        // 全表示フラグ確認 2024/06/07
        if (isset($_GET['show_all_flg'])) {
            $show_all_flg = $_GET['show_all_flg'];
        }

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // SQL 修正 24_0522 最新
        $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
                      ,RZ.棚番
                      ,SH.梱包入数
                      ,SUM(SL.数量) AS 数量    
                      ,SUM(PK.ピッキング数量) AS ピッキング数量
                      ,PK.処理Ｆ
                      ,SH.ＪＡＮ
                      ,SK.特記事項
                  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                      ,RZMF RZ
                      ,HTPK PK
                 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                   AND SK.伝票行番号 = SL.伝票行番号
                   AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
                   AND SL.伝票番号   = PK.伝票番号(+)
                   AND SL.伝票行番号 = PK.伝票行番号(+)
                   AND SL.伝票行枝番 = PK.伝票行枝番(+)
                   AND SL.倉庫Ｃ = SO.倉庫Ｃ
                   AND SL.出荷元 = SM.出荷元Ｃ(+)
                   AND SJ.運送Ｃ = US.運送Ｃ
                   AND SL.商品Ｃ = SH.商品Ｃ
                   AND SL.倉庫Ｃ = RZ.倉庫Ｃ
                   AND SL.商品Ｃ = RZ.商品Ｃ
                   AND SJ.出荷日 = :SELECT_DATE     
                   AND SL.倉庫Ｃ = :SELECT_SOUKO
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02";

        // 可変部分の条件を追加
        if (!empty($conditions)) {
            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        //2024/06/07修正 全表示
        if (!isset($_GET["show_all_flg"])) {
            $sql .= " AND NVL(PK.処理Ｆ,0) <> 9 ";
        } else {
            $all_flg = 1;
        }

        // GROUP BY句とORDER BY句を追加
        $sql .= " GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項 ";

        // 2024/06/05
        switch ($sortKey) {
            case 'location_note':
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'num_note':
                $sql .= "ORDER BY 数量,SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'tokki_note':
                $sql .= "ORDER BY SK.特記事項,数量,SL.倉庫Ｃ,SJ.運送Ｃ,SL.商品Ｃ,SL.出荷元 ";
                break;

            case 'bikou_note':
                $sql .= "ORDER BY SM.出荷元名,数量,SL.倉庫Ｃ,SJ.運送Ｃ,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            default:
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;
        }

        // === SQL 格納
        $sql_one_tokki = $sql;
        $_SESSION['sql_one_option'] = $sql_one_tokki;

        dprint($sql);
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }


        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
        oci_bind_by_name($stid, ":SELECT_SOUKO_02", $select_souko_code);

        // dprint($sql);

        oci_execute($stid);


        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $tokki_zikou     = $row['特記事項'];

            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                'Unsou_code' => $Unsou_code,                // SJ.運送Ｃ
                'Unsou_name' => $Unsou_name,                // US.運送略称
                'shipping_moto' => $shipping_moto,          // SL.出荷元
                'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                'shouhin_JAN' => $shouhin_JAN,               // JANコード
                'tokki_zikou ' => $tokki_zikou,
                'sql_one_tokki' => $sql_one_tokki,
                'four_status' => 'one_bikou_tokki'
            );
        }

        oci_free_statement($stid);
        oci_close($conn);


        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $get_souko_name = $_SESSION['soko_name'];
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }

        // =================================================
        // ==================== 複数選択   third.php => four.php  ===================
        // =================================================
    } else if ((isset($_GET['fukusuu_select']) && $_GET['fukusuu_select'] === '200')
        || isset($_SESSION['fukusuu_select']) && $_SESSION['fukusuu_select'] === '200'
        || isset($_GET['four_status']) && $_GET['four_status'] == 'multiple_sql_four'
//        || !isset($_GET['sort_areaa']) && !isset($_GET['sort_areab'])
//        || isset($_GET['sort_key']) && isset($_GET['sort_areac'])
//        || isset($_GET["show_all_flg"]) && $_GET["show_all_flg"] == 2
//        || isset($_GET["all_flg"]) && $_GET["all_flg"] == 2
    ) {


        dprint("複数選択 , デフォルト:::" . "<br>");
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        $get_souko_name = $_GET['get_souko_name'];
        $select_unsou_code = $_GET['unsou_code'];
        $get_unsou_name = $_GET['unsou_name'];


        // 複数  運送コード + 特記・備考
        $fukusuu_unsouo_num = $_GET['fukusuu_unsouo_num'];
        $fukusuu_select_val = $_GET['fukusuu_select_val'];

        // === セッションへ格納
        if (isset($_SESSION['fukusuu_unsouo_num']) && !empty($_SESSION['fukusuu_unsouo_num'])) {
            $fukusuu_unsouo_num = $_SESSION['fukusuu_unsouo_num'];
        } else {
            $_SESSION['fukusuu_unsouo_num'] = $fukusuu_unsouo_num;
        }

        // === セッションへ格納
        if (isset($_SESSION['fukusuu_select_val']) && !empty($_SESSION['fukusuu_select_val'])) {
            $fukusuu_select_val = $_SESSION['fukusuu_select_val'];
        } else {
            $_SESSION['fukusuu_select_val'] = $fukusuu_select_val;
        }

        if (isset($_SESSION['fukusuu_select']) && !empty($_SESSION['fukusuu_select'])) {
            $fukusuu_select = $_SESSION['fukusuu_select'];
        } else {
            $_SESSION['fukusuu_select'] = $fukusuu_select;
        }


        // 複数　運送コード 分割
        $arr_fukusuu_unsouo_num = explode(',', $fukusuu_unsouo_num);
        // 複数  運送コード + 特記・備考 分割
        $arr_fukusuu_select_val = explode(',', $fukusuu_select_val);

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // ========= 運送コード + 特記・備考　処理
        // コロン & 空白要素　削除
        $arr_fukusuu_select_val[0] = str_replace('：', '', $arr_fukusuu_select_val[0]);
        $arr_fukusuu_select_val = array_filter($arr_fukusuu_select_val);

        // 運送コードの 先頭の - 削除
        foreach ($arr_fukusuu_select_val as &$arr_fukusuu_VAL) {
            $arr_fukusuu_VAL = preg_replace('/^-/', '', $arr_fukusuu_VAL);
        }
        unset($arr_fukusuu_VAL);

        // ========= 運送コード　複数
        $arr_fukusuu_unsouo_num = explode(',', $fukusuu_unsouo_num);
        $arr_fukusuu_unsouo_num[0] = str_replace('：', '', $arr_fukusuu_unsouo_num[0]);
        $arr_fukusuu_unsouo_num = array_filter($arr_fukusuu_unsouo_num);

        $arr_Fku_Val = [];

        // 運送コード 運送コード + 特記・備考 の２次元配列作成
        foreach ($arr_fukusuu_select_val as $arr_val) {
            $arr_Fku_Val[] = explode(":", $arr_val);
        }

        // ********* 可変部分の条件を生成 *********
        if (!empty($arr_Fku_Val)) {

            $conditions = [];
            foreach ($arr_Fku_Val as $arr_SQL) {
                // 可変部分の条件を生成
                $conditionSet = [];
                $conditionSet[0] = "(SJ.運送Ｃ = '{$arr_SQL[1]}')";

                if ($arr_SQL[2] !== '-') {
                    $conditionSet[1] = "(SL.出荷元 = '{$arr_SQL[2]}')";
                } else {
                    $conditionSet[1] = "(SL.出荷元 IS NULL)";
                }

                if ($arr_SQL[3] !== '---') {
                    $conditionSet[2] = "(SK.特記事項 = '{$arr_SQL[3]}')";
                } else {
                    $conditionSet[2] = "(SK.特記事項 IS NULL)";
                }

                // 条件を結合して配列に追加
                if (empty($fukusuu_unsouo_num)) {
                    // OK 
                    $conditions[] = implode(' AND ', $conditionSet);
                } else {

                    //   $conditions[] = implode(' AND ', $conditionSet);
                    $conditions[] = '(' . implode(' AND ', $conditionSet) . ')';
                }
            }
        }

        // 並替ボタン選択時 2024/06/05
        $sortKey = "";
        if (isset($_GET['sort_key'])) {
            $sortKey = $_GET['sort_key'];
        }

        // 全表示フラグ確認 2024/06/07
        if (isset($_GET['show_all_flg'])) {
            $show_all_flg = $_GET['show_all_flg'];
        }

        // === 運送コード（備考・特記）なし抽出　重複削除 *php8 修正
        if (!empty($arr_Fku_Val)) {
            $idx = 0;
            for ($i = 0; $i < count($arr_fukusuu_unsouo_num); $i++) {
                // キーが存在するかを確認
                if (isset($arr_Fku_Val[$idx][1]) && $arr_fukusuu_unsouo_num[$idx] == $arr_Fku_Val[$idx][1]) {
                    unset($arr_fukusuu_unsouo_num[$idx]);
                    break;
                }
                $idx = $idx + 1;
            }
        }


        if (empty($arr_fukusuu_unsouo_num)) {
            dprint("複数は空");
        }


        $conditions_UNSOU = [];
        // === 運送コードだけのものがあった場合
        if (!empty($arr_fukusuu_unsouo_num)) {

            $idx = 0;
            foreach ($arr_fukusuu_unsouo_num as $F_Unsou_VAL) {

                // print($F_Unsou_VAL . "<br>");

                $conditionSet_Unsou = [];
                $conditionSet_Unsou[0] = "(SJ.運送Ｃ = '{$F_Unsou_VAL}')";
                //      $idx = $idx + 1;

                if (empty($arr_Fku_Val)) {
                    //
                    $conditions_UNSOU[] = '(' . implode(' OR ', $conditionSet_Unsou) . ')';
                } else {
                    // $conditions_UNSOU[] = '(' . implode(' OR ', $conditionSet_Unsou) . ')';
                    $conditions_UNSOU[] = implode(' OR ', $conditionSet_Unsou);
                }
            }
        }

        $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名	
                      ,RZ.棚番
                      ,SH.梱包入数
                      ,SUM(SL.数量) AS 数量
                      ,SUM(PK.ピッキング数量) AS ピッキング数量
                      ,PK.処理Ｆ
                      ,SH.ＪＡＮ
                      ,SK.特記事項
                  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                      ,RZMF RZ
                      ,HTPK PK
                 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                   AND SK.伝票行番号 = SL.伝票行番号
                   AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
                   AND SL.伝票番号   = PK.伝票番号(+)
                   AND SL.伝票行番号 = PK.伝票行番号(+)
                   AND SL.伝票行枝番 = PK.伝票行枝番(+)
                   AND SL.倉庫Ｃ = SO.倉庫Ｃ
                   AND SL.出荷元 = SM.出荷元Ｃ(+)
                   AND SJ.運送Ｃ = US.運送Ｃ
                   AND SL.商品Ｃ = SH.商品Ｃ
                   AND SL.倉庫Ｃ = RZ.倉庫Ｃ
                   AND SL.商品Ｃ = RZ.商品Ｃ
                   AND SJ.出荷日 = :SELECT_DATE     
                   AND SL.倉庫Ｃ = :SELECT_SOUKO
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02";

        // 可変部分の条件を追加 、運送コード + 備考・特記
        if (!empty($conditions) && !empty($conditions_UNSOU)) {
            //    $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
            $sql .= ' AND (' . implode(' OR ', $conditions);
        } else if (!empty($conditions) && empty($conditions_UNSOU)) {
            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        if (!empty($conditions_UNSOU) && !empty($conditions)) {
            //  $sql .= ' OR (' . implode(' OR ', $conditions_UNSOU) . ')';
            $sql .= ' OR ' . implode(' OR ', $conditions_UNSOU) . ')';
        } else if (!empty($conditions_UNSOU) && empty($conditions)) {
            $sql .= ' AND (' . implode(' OR ', $conditions_UNSOU) . ')';
        }

        // 可変部分の条件を追加 、運送コード

        //2024/06/07 全表示
        if (!isset($_GET["show_all_flg"])) {
            $sql .= "AND NVL(PK.処理Ｆ,0) <> 9 ";
        } else {
            $all_flg = 2;
        }

        // GROUP BY句とORDER BY句を追加
        $sql .= "GROUP BY SJ.出荷日, SL.倉庫Ｃ, SO.倉庫名, 
        SL.出荷元, SM.出荷元名, SL.商品Ｃ, SH.品名, PK.処理Ｆ, RZ.棚番, SH.梱包入数, SH.ＪＡＮ, SK.特記事項 ";

        // 2024/06/05
        switch ($sortKey) {
            case 'location_note':  //ロケ順
                $sql .= "ORDER BY RZ.棚番,SL.商品Ｃ,SL.出荷元,SK.特記事項,数量";
                break;

            case 'num_note':       //数量順
                $sql .= "ORDER BY 数量,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'tokki_note':     //特記順
                $sql .= "ORDER BY SK.特記事項,SL.出荷元,RZ.棚番,SL.商品Ｃ";
                break;

            case 'bikou_note':    //備考順
                $sql .= "ORDER BY SL.出荷元,SK.特記事項,RZ.棚番,SL.商品Ｃ";
                break;

            default:             //デフォルトはロケ順
                $sql .= "ORDER BY RZ.棚番,SL.商品Ｃ,SL.出荷元,SK.特記事項,数量";
                break;
        }

        // =================
        // === 複数SQL　取得
        // =================

        $Multiple_Sql = $sql;

        // *** sqlをセッションへ格納 ***
        $_SESSION['multiple_sql'] = $Multiple_Sql;

        //$sql_multiple_cut = getCondition_Multiple($Multiple_Sql);
        //  dprint($sql_multiple_cut);
        dprintBR("********* Multiple_Sql デフォルト **********");
        dprintBR($Multiple_Sql);
        dprintBR($show_all_flg);
        if ($show_all_flg <> "") {
            // 全数表示時 条件抜き出し
            $sql_multiple_cut = getCondition_Multiple_zen($Multiple_Sql);
            dprintBR("********* getCondition_Multiple_zen デフォルト **********");
            dprintBR($sql_multiple_cut);
        } else {
            // 通常時表示時 条件抜き出し
            $sql_multiple_cut = getCondition_Multiple($Multiple_Sql);
            dprintBR("********* getCondition_Multiple デフォルト **********");
            dprintBR($sql_multiple_cut);
        }

        $_SESSION['multiple_sql_cut'] = $sql_multiple_cut;

        // *** sqlをセッションへ格納 END ***

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
        oci_bind_by_name($stid, ":SELECT_SOUKO_02", $select_souko_code);

        dprint($sql);

        oci_execute($stid);



        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            //    $Unsou_code = $row['運送Ｃ'];
            //    $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            //24/05/24
            //          $Tokuisaki_name = $row['得意先名'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $tokki_zikou    = $row['特記事項'];

            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                //    'Unsou_code' => $Unsou_code,                // SJ.運送Ｃ
                //    'Unsou_name' => $Unsou_name,                // US.運送略称
                'shipping_moto' => $shipping_moto,          // SL.出荷元
                'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                //24/05/24
                //              'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                'shouhin_JAN' => $shouhin_JAN,               // JANコード
                'tokki_zikou' => $tokki_zikou,
                'four_status' => 'multiple_sql_four'
                //          'Multiple_Sql' => $Multiple_Sql
            );
        }

        oci_free_statement($stid);
        oci_close($conn);

        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $get_souko_name = $_SESSION['soko_name'];
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }
    } // ================================================== END 


    if (isset($_GET['back_flg'])) {
        $back_flg = $_GET['back_flg'];
        // print($back_flg);
    }

    // =========
    // janテスト　
    // =========
    if (isset($_GET['scan_b'])) {
        $sortKey = 'location_note';
        //    print($_GET['scan_b'] . "ここ");
    }


    // ============================= HTPK テーブル 処理 =============================
    // === 接続準備
    $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

    if (!$conn) {
        $e = oci_error();
    }

    //   $sql = "SELECT 商品Ｃ,処理Ｆ,商品名 FROM HTPK WHERE 処理Ｆ = 2 and 登録日時 = :syuka_day";
    $sql = "SELECT 商品Ｃ,処理Ｆ,商品名 FROM HTPK WHERE 処理Ｆ = 2 OR 処理Ｆ = 8 OR 処理Ｆ = 9";
    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($stid);
    }

    // oci_bind_by_name($select_seq_stid, ':syuka_day', $syuka_day);

    oci_execute($stid);

    $arr_Zumi_DATA = array();
    while ($row = oci_fetch_assoc($stid)) {
        // カラム名を指定して値を取得
        $HTPK_Souhin_Code = $row['商品Ｃ'];
        $HTPK_Sori_Flg = $row['処理Ｆ'];
        $HTPK_Souhin_Name = $row['商品名'];

        // 取得した値を配列に追加
        $arr_Zumi_DATA[] = array(
            'HTPK_Souhin_Code' => $HTPK_Souhin_Code,
            'HTPK_Sori_Flg' => $HTPK_Sori_Flg,
            'HTPK_Souhin_Name' => $HTPK_Souhin_Name,
        );
    }

    // var_dump($arr_Zumi_DATA);

    oci_free_statement($stid);
    oci_close($conn);
}

?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/forth.css">
    <link rel="stylesheet" href="./css/third.css">
    <link rel="stylesheet" href="./css/common.css">

    <link href="./css/all.css" rel="stylesheet">

    <!-- jQuery cdn -->
    <script src="./js/jquery.min.js"></script>

    <title>ピッキング対象選択</title>


</head>

<body>

    <div class="head_box">
        <div class="head_content">
            <span class="home_icon_span">
                <a href="#"><img src="./img/home_img.png"></a>
            </span>

            <span class="App_name">
                グリーンライフ ピッキング
            </span>
        </div>
    </div>

    <div id="app">
        <div class="head_box_02">
            <div class="head_content_02">
                <span class="home_sub_icon_span">
                    <a href="#"><img src="./img/page_img.png"></a>
                </span>

                <span class="page_title">
                    ピッキング対象選択
                </span>
            </div>
        </div>

        <div class="container">
            <div class="content_04">
                <div class="head_01">

                    <div>
                        <span class="souko_icon_box">
                            <img src="./img/souko_img.png">
                        </span>
                        <span class="souko_icon_box">
                            <?php echo h($get_souko_name); ?>
                        </span>
                    </div>

                    <div>
                        <span class="unsou_icon_box">
                            <img src="./img/unsou_img.png">
                        </span>

                        <span class="unsou_text_box">
                            <?php echo h($get_unsou_name); ?>
                        </span>
                    </div>

                </div>

                <div class="" id="menu_btn_box">

                    <div class="dropdown_02" @click="toggleDropdown(1)">
                        <button class="dropbtn" id="order_btn" value="並替">並替</button>
                        <div class="dropdown-content" :class="{show: isOpen[1]}">
                            <button type="button" @click="handleButtonClick('location_note')">ロケ順</button>
                            <button type="button" @click="handleButtonClick('num_note')">数量順</button>
                            <button type="button" @click="handleButtonClick('tokki_note')">特記順</button>
                            <button type="button" @click="handleButtonClick('bikou_note')">備考順</button>
                        </div>
                    </div>

                    <div>

                        <button type="button" id="all_select_btn">
                            全表示
                        </button>

                    </div>

                </div>


                <div class="cp_iptxt_03">
                    <label class="ef_03">
                        <input type="number" id="get_JAN" name="get_JAN" placeholder="Scan JAN">
                    </label>
                </div>
                <p id="err_JAN" style="color:red;"></p>


                <hr class="hr_01">

            </div> <!-- head_01 END -->

            <!-- ********* テストsql 運送コード単数 , 備考・特記あり ********* -->
            <?php if (isset($arr_Picking_DATA[0]['sql_one_tokki'])) : ?>
                <?php
                $one_condition = getCondition($arr_Picking_DATA[0]['sql_one_tokki']);
                //  print($one_condition);
                ?>
                <input type="hidden" name="sql_one_tokki" id="sql_one_tokki" value="<?php print $one_condition; ?>">
            <?php endif; ?>

            <!-- ********* five.php 「戻る」 ********* -->
            <?php if (isset($_GET['back_one_condition'])) : ?>
                <input type="hidden" name="back_sql_one_tokki" id="back_sql_one_tokki" value="<?php print $back_one_condition; ?>">
            <?php endif; ?>

            <!-- ********* 複数処理の場合 ********* -->
            <?php if (isset($arr_Picking_DATA[0]['Multiple_Sql'])) : ?>
                <?php
                $Multiple_condition = getCondition_Multiple($arr_Picking_DATA[0]['Multiple_Sql']);
                //     dprint($Multiple_condition);
                ?>
                <input type="hidden" name="Multiple_condition" id="Multiple_condition" value="<?php print $Multiple_condition; ?>">
            <?php endif; ?>

            <!-- ==================================================== -->
            <!-- ============== テーブルレイアウト 開始 =============== -->
            <!-- ==================================================== -->
            <div id="select_view_box">
                <table border="1">
                    <thead>
                        <tr>
                            <th>ロケ</th>
                            <th>数量</th>
                            <th>ケース</th>
                            <th>バラ</th>
                            <th>品名・品番</th>
                            <th>特記・備考</th>
                        </tr>

                        <?php


                        // Sagyou_NOW_Flg = 2 : 残
                        // Sagyou_NOW_Flg = 1 : 選択中
                        // Sagyou_NOW_Flg = 0 : 作業前 

                        foreach ($arr_Picking_DATA as $Picking_VAL) {

                            $Sagyou_NOW_Flg = 0;

                            $shouhin_name_part1 = mb_substr($Picking_VAL['Shouhin_name'], 0, 20);
                            $shouhin_name_part2 = mb_substr($Picking_VAL['Shouhin_name'], 20);


                            // print("処理F:::" . $Picking_VAL['Shori_Flg']);

                            /*
                            foreach ($arr_Zumi_DATA as $Zumi_DATA) {

                                if ($Picking_VAL['Shouhin_code'] == $Zumi_DATA['HTPK_Souhin_Code'] && $Picking_VAL['Shouhin_name'] == $Zumi_DATA['HTPK_Souhin_Name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 2) {
                                    $Sagyou_NOW_Flg = 1;
                                    //print("Sagyou_NOW_Flg" . $Sagyou_NOW_Flg);
                                    break;
                                } else if ($Picking_VAL['Shouhin_code'] == $Zumi_DATA['HTPK_Souhin_Code'] && $Picking_VAL['Shouhin_name'] == $Zumi_DATA['HTPK_Souhin_Name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 8) {
                                    $Sagyou_NOW_Flg = 2;
                                    //print("Sagyou_NOW_Flg" . $Sagyou_NOW_Flg);
                                    break;
                                } else if ($Picking_VAL['Shouhin_code'] == $Zumi_DATA['HTPK_Souhin_Code'] && $Picking_VAL['Shouhin_name'] == $Zumi_DATA['HTPK_Souhin_Name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 9 && isset($_GET["show_all"])) {
                                    $Sagyou_NOW_Flg = 3;
                                    //print("Sagyou_NOW_Flg" . $Sagyou_NOW_Flg);
                                    break;
                                } else if ($Zumi_DATA['HTPK_Souhin_Code'] == $Picking_VAL['Shouhin_code'] && $Zumi_DATA['HTPK_Souhin_Name'] == $Picking_VAL['Shouhin_name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 9) {
                                    $Sagyou_NOW_Flg = 4;
                                    //print("Sagyou_NOW_Flg" . $Sagyou_NOW_Flg);
                                    break;
                                }
                            } // =========== END arr_Zumi_DATA
                             */


                            // ケース薄 計算
                            if ($Picking_VAL['Shouhin_num'] != 0 && $Picking_VAL['Konpou_num']) {
                                // ケース数
                                $Case_num_View = floor($Picking_VAL['Shouhin_num'] / $Picking_VAL['Konpou_num']);
                            } else {
                                $Case_num_View = 0;
                            }

                            // バラ数 計算
                            if ($Picking_VAL['Konpou_num'] != 0) {
                                $Bara_num_View = $Picking_VAL['Shouhin_num'] % $Picking_VAL['Konpou_num'];
                            } else {
                                $Bara_num_View = 0;
                            }

                            // === 処理フラグ
                            $Shori_Flg = $Picking_VAL['Shori_Flg'];

                            //       dprint("処理F:::" . $Shori_Flg);

                            // 完了
                            if ($Shori_Flg == 9) {
                                $Sagyou_NOW_Flg = 3;
                            }

                            // 作業中にする
                            if ($Shori_Flg == 2) {
                                $Sagyou_NOW_Flg = 1;
                            }


                            if ($Sagyou_NOW_Flg == 0) {

                                // === 運送便（単数）, 備考・特記あり
                                //           if (isset($Picking_VAL['sql_one_tokki']) && $Picking_VAL['sql_one_tokki'] != "" && $Picking_VAL['four_status'] == 'one_bikou_tokki') {
                                if (isset($Picking_VAL['sql_one_tokki']) && $Picking_VAL['sql_one_tokki'] != "" && $Picking_VAL['four_status'] == 'one_bikou_tokki') {
                                    $encoded_sql_one_tokki = UrlEncode_Val_Check($sql_one_tokki);

                                    if (isset($Picking_VAL['tokki_zikou'])) {
                                        echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($Picking_VAL['Unsou_name']) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&now_sql=' . $encoded_sql_one_tokki . '">';
                                    } else {
                                        $Picking_VAL['tokki_zikou'] = "";
                                        echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($Picking_VAL['Unsou_name']) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&now_sql=' . $encoded_sql_one_tokki . '">';
                                    }


                                    // === 運送便（複数） , 備考・特記あり,　※備考・特記ありも複数　=> five.php から戻ってきた
                                } else if (isset($Picking_VAL['sql_multiple_tokki']) && $Picking_VAL['sql_multiple_tokki'] != "") {

                                    dprint("ここ:複数");
                                    $Multiple_Sql_Url = UrlEncode_Val_Check($Picking_VAL['sql_multiple_tokki']);
                                    echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($Picking_VAL['Unsou_name']) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .  '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '">';

                                    // *** 複数が最初に通る ルート ***
                                    // four.php => five.php へ　運送便（複数） , 備考・特記あり,　※備考・特記ありも複数
                                } else if (isset($Picking_VAL['four_status']) && $Picking_VAL['four_status'] == "multiple_sql_four") {

                                    dprint("ここ:複数 最初のルート third.php => four.php => five.php");

                                    echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' .
                                        UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&shipping_moto='
                                        . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name'])
                                        . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name'])
                                        . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num'])
                                        . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' .
                                        UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) .
                                        '&four_five_multiple_sql=' . $sql_multiple_cut . '&four_status=multiple_sql_four' . '">';
                                } else {

                                    // === 通常処理　特記事項 あり
                                    if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['four_status'] == 'default_root' && $Picking_VAL['tokki_zikou'] != "" || $Picking_VAL['shipping_moto'] != "") {
                                        //    dprint("koko,// === 通常処理　特記事項 あり");
                                        echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($Picking_VAL['Unsou_code']) . '&unsou_name=' . UrlEncode_Val_Check($Picking_VAL['Unsou_name']) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' .  UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&four_status=default_root' . '">';
                                    } else if ($Picking_VAL['four_status'] == 'default_root' && $Picking_VAL['tokki_zikou'] == "" && $Picking_VAL['shipping_moto'] == "") {
                                        // dprint("koko,// === 通常処理　特記事項 あり else");
                                        // === 通常処理　特記事項 あり
                                        echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($Picking_VAL['Unsou_code']) . '&unsou_name=' . UrlEncode_Val_Check($Picking_VAL['Unsou_name']) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' .  UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&four_status=default_root' . '&status_sub=default' . '">';
                                    }
                                }


                                echo '<td>' . $Picking_VAL['Tana_num'] . '</td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                    '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                                echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                                echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' .
                                    '<span class="Shouhin_name_default_root">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                // === 特記がある
                                if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    // === 特記がない
                                    echo '<td><span class="toki_list">' . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                }


                                echo '</tr>';

                                // ===================================
                                //============== 作業中 ==============
                                // ===================================
                            } else if ($Sagyou_NOW_Flg == 1) {
                                echo '<tr style="background: yellow;" id="sagyou_now" class="sagyou_now">';
                                echo '<td><span id="sagyou_now_text">作業中</span>' .
                                    '<span class="sagyou_img_box" style="display: block;margin: 10px 0 0 0;">' . $Picking_VAL['Tana_num'] . '</span></td>';

                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                    '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                                echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                                echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' .
                                    '<span class="Shouhin_name_default_root">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                // === 特記
                                if ((isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") || (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "")) {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                }


                                echo '</tr>';
                            } else if ($Sagyou_NOW_Flg == 2) {

                                if (isset($Picking_VAL['sql_one_tokki']) && $Picking_VAL['sql_one_tokki'] != "") {

                                    if (isset($Picking_VAL['Denpyou_SEQ']) && $Picking_VAL['Denpyou_SEQ'] != "") {
                                        $encoded_sql_one_tokki = UrlEncode_Val_Check($sql_one_tokki);
                                        echo '<tr style="background: green;" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) .
                                            '&souko_code=' . UrlEncode_Val_Check($select_souko_code) .
                                            '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) .
                                            '&unsou_name=' . UrlEncode_Val_Check($Picking_VAL['Unsou_name']) .
                                            '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) .
                                            '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) .
                                            '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) .
                                            '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) .
                                            '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .
                                            '&Tokuisaki=' . UrlEncode_Val_Check($Tokuisaki_name) .
                                            '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) .
                                            '&case_num=' . UrlEncode_Val_Check($Case_num_View) .
                                            '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                            '&shouhin_jan=' . UrlEncode_Val_Check($shouhin_JAN) .
                                            '&tokki_zikou=' . $Picking_VAL['tokki_zikou'] .
                                            '&Denpyou_SEQ=' . UrlEncode_Val_Check($Picking_VAL['Denpyou_SEQ']) . // 伝票 SEQ
                                            '&now_sql=' . $encoded_sql_one_tokki . '">';
                                    } else {

                                        $encoded_sql_one_tokki = UrlEncode_Val_Check($sql_one_tokki);
                                        echo '<tr style="background: green;" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) .
                                            '&souko_code=' . UrlEncode_Val_Check($select_souko_code) .
                                            '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) .
                                            '&unsou_name=' . UrlEncode_Val_Check($Picking_VAL['Unsou_name']) .
                                            '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) .
                                            '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) .
                                            '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) .
                                            '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) .
                                            '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .
                                            '&Tokuisaki=' . UrlEncode_Val_Check($Tokuisaki_name) .
                                            '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) .
                                            '&case_num=' . UrlEncode_Val_Check($Case_num_View) .
                                            '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                            '&shouhin_jan=' . UrlEncode_Val_Check($shouhin_JAN) .
                                            '&now_sql=' . $encoded_sql_one_tokki . '">';
                                    }
                                } else {

                                    echo '<tr style="background: green"; data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($Picking_VAL['Unsou_name']) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&Tokuisaki=' . UrlEncode_Val_Check($Tokuisaki_name) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($shouhin_JAN) . '">';
                                }

                                echo '<td><span id="sagyou_now_text">残<i class="fa-regular fa-circle-stop"></i></span>' . $Picking_VAL['Tana_num'] . '</td>';

                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                    '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                                echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                                echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' .
                                    '<span class="Shouhin_name_default_root">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                // === 特記
                                if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                }

                                echo '</tr>';
                            } else if ($Sagyou_NOW_Flg == 3 || $Shori_Flg == 9) {

                                // === 全数表示
                                $row_class = 'picking-row1';
                                // 2024/06/07 echoコメント
                                if ($Picking_VAL['now_sql'] != "") {
                                    $encoded_sql_one_tokki = UrlEncode_Val_Check($Picking_VAL['now_sql']);
                                    //    echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num']  . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';
                                } else {

                                    //    echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';
                                }

                                echo '<tr style="background: #99CCFF;">';
                                echo '<td><span id="sagyou_now_text_ok">作業完了<i class="fa-regular fa-circle-stop"></i><br></span>' . $Picking_VAL['Tana_num'] . '</td>';

                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                    '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                                echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                                echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' .
                                    '<span class="Shouhin_name_default_root">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';

                                echo '</tr>';
                            } else if ($Sagyou_NOW_Flg == 4) {
                                // 「確定」
                                $row_class = 'picking-row2';
                                echo '<tr class="' . $row_class . '" data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '">';
                                echo '<td><span id="sagyou_now_text_ok">作業完了<i class="fa-regular fa-circle-stop"></i><br></span>' . $Picking_VAL['Tana_num'] . '</td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                                echo '<td>' .  $Case_num_View . '</td>';
                                echo '<td>' . $Bara_num_View . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                // === 特記
                                if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                }

                                echo '</tr>';
                            } /* else if ($Sagyou_NOW_Flg == 55) {

                                $encoded_sql_one_tokki = UrlEncode_Val_Check($sql_one_tokki);
                                echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';

                                echo '<td>' . $Picking_VAL['Tana_num'] . '</td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                                echo '<td>' .  $Case_num_View . '</td>';
                                echo '<td>' . $Bara_num_View . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                echo '<td><span class="toki_list">' . $Picking_VAL['Toki_Zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            }
                            */
                        }


                        ?>

                    </thead>

                </table>


            </div> <!-- head_02 -->

        </div> <!-- ======== END container ========= -->

        <!-- 全数完了　「戻り」 five.php => four.php -->
        <?php

        /*
        if (isset($_SESSION['one_now_sql_zensuu'])) {
            echo '<input type="hidden" name="one_now_sql_zensuu" id="one_now_sql_zensuu" value="' . h($_SESSION['one_now_sql_zensuu']) . '">';
        }
        */

        /*
        if (isset($_GET['selectedToki_Code']) && $_GET['selectedToki_Code'] != "") {

        }
        */

        ?>



        <!-- フッターメニュー -->
        <footer class="footer-menu">
            <ul>
                <?php $back_flg = 1; ?>
                <?php $url = "./third.php?selectedSouko=" . UrlEncode_Val_Check($select_souko_code) . "&selected_day=" . UrlEncode_Val_Check($select_day) . "&souko_name=" . UrlEncode_Val_Check($get_souko_name) . "&back_flg=" . $back_flg; ?>
                <li><a href="<?php echo $url; ?>">戻る</a></li>
                <li><a href="" id="Kousin_Btn">更新</a></li>
            </ul>
        </footer>


    </div> <!-- ======== END app ========= -->



    <script src="./js/vue@2.js"></script>
    <script>
        new Vue({
            el: '#app',
            data: {
                isOpen: {
                    1: false,
                },
                selectValue: ''
            },
            methods: {
                // === トグル open, close 処理
                toggleDropdown(menuId) {
                    this.isOpen[menuId] = !this.isOpen[menuId];
                },
                // === プルダウンボタンの値取得処理
                handleButtonClick(value) {
                    this.selectValue = value;
                    console.log("プルダウン:::" + this.selectValue);

                    var selectedDay = '<?php echo $select_day; ?>';
                    var select_souko_code = '<?php echo $select_souko_code; ?>';
                    var get_unsou_name = '<?php echo $get_unsou_name; ?>';
                    var select_unsou_code = '<?php echo $select_unsou_code; ?>';
                    var get_souko_name = '<?php echo $get_souko_name; ?>';
                    // 2024/06/07 全表示後の並替用
                    var show_all_flg = "<?php echo isset($show_all_flg) ? $show_all_flg : ''; ?>";
                    var all_flg = "<?php echo isset($all_flg) ? $all_flg : ''; ?>";
                    
                    // 複数選択 2024/06/07
                    var fukusuu_unsouo_num = "<?php echo isset($fukusuu_unsouo_num) ? $fukusuu_unsouo_num : ''; ?>";
                    // 2024/06/12
                    var fukusuu_select = "<?php echo isset($fukusuu_select) ? $fukusuu_select : ''; ?>";
                    var fukusuu_select_val = "<?php echo isset($fukusuu_select_val) ? $fukusuu_select_val : ''; ?>";
                    // 2024/06/12
                    var select_toki_code = "<?php echo isset($selectedToki_Code) ? $selectedToki_Code : ''; ?>";


                    if (show_all_flg != "") {
                        // 選択条件分岐 2024/06/07 修正
                        if (fukusuu_select != "") {
                            var sortarea = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_unsouo_num + '&fukusuu_select=' + fukusuu_select + '&fukusuu_select_val=' + fukusuu_select_val + '&sort_key=' + this.selectValue + '&sort_areac=' + sort_area + '&show_all_flg=' + show_all_flg + '&all_flg=' + all_flg;
                        } else if (select_toki_code != "") {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + this.selectValue + '&sort_areab=' + sort_area + '&show_all_flg=' + show_all_flg + '&all_flg=' + all_flg;
                        } else {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + this.selectValue + '&sort_areaa=' + sort_area + '&show_all_flg=' + show_all_flg + '&all_flg=' + all_flg;
                        }


                    } else {
                        // 選択条件分岐 2024/06/07 修正
                        if (fukusuu_select != "") {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_unsouo_num + '&fukusuu_select=' + fukusuu_select + '&fukusuu_select_val=' + fukusuu_select_val + '&sort_key=' + this.selectValue + '&sort_areac=' + sort_area;
                        } else if (select_toki_code != "") {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + this.selectValue + '&sort_areab=' + sort_area;
                        } else {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + this.selectValue + '&sort_areaa=' + sort_area;
                        }
                    }

                    window.location.href = url;

                }
            },

        });
    </script>

    <script>
        $(document).ready(function() {

            // 全角を半角に変換
            function convertToHalfWidth(input) {
                return input.replace(/[Ａ-Ｚａ-ｚ０-９]/g, function(s) {
                    return String.fromCharCode(s.charCodeAt(0) - 65248); // 全角文字のUnicode値から半角文字に変換
                });
            }

            // JAN のテキストにフォーカスを当てる
            $('#get_JAN').focus();
            $('#get_JAN').val("");

            // JAN エラーフラグ
            var Jan_Flg = 0;

            // JAN 判定
            //  $('#get_JAN').blur(function() {
            $('#get_JAN').change(function() {

                Jan_Flg = 0;

                var input_JAN = $('#get_JAN').val();
                var convertedValue_JAN = convertToHalfWidth(input_JAN);
                $(this).val(convertedValue_JAN);

                // JAN コード判定
                $(".shouhin_JAN").each(function() {
                    console.log("item:::" + $(this).val() + "\n");
                    var shouhin_JAN = $(this).val();

                    // *** 商品コード 取得ループ start
                    var Shouhin_code_val_values = [];
                    $(this).closest('tr').find('.Shouhin_code_val').each(function() {
                        // .Shouhin_code_valクラスの値を配列に追加
                        Shouhin_code_val_values.push($(this).val());
                    });
                    // *** 商品コード 取得ループ END


                    // JAN
                    if (shouhin_JAN === $('#get_JAN').val()) {
                        var parentElement = $(this).closest('tr');


                        if (!parentElement.hasClass('sagyou_now')) { // 'sagyou_now'クラスがない場合のみ遷移する
                            var dataHref = parentElement.data('href') + "&scan_b=bar_san";
                            console.log("値一致:::" + dataHref);

                            // 
                            for (var i = 0; i < Shouhin_code_val_values.length; i++) {
                                console.log("商品コード ループ HIT:::" + Shouhin_code_val_values[i]);
                            }

                            window.location.href = dataHref;

                            Jan_Flg = 1;
                            // return false;
                        } else {
                            console.log("sagyou_nowクラスが付いているため、画面遷移しません。");
                            Jan_Flg = 11;
                            // return false;
                        }

                    }

                });

                // JAN エラーメッセージ
                if (Jan_Flg === 0) {
                    $('#err_JAN').html("JAN コードに一致する商品がありません。<br>値：(" + $('#get_JAN').val() + ")");
                    $('#get_JAN').val("");
                    $('#get_JAN').focus();
                } else if (Jan_Flg === 1) {
                    $('#err_JAN').html("対象のJANコード商品へ遷移します。");
                    $('#get_JAN').val("");
                    $('#get_JAN').focus();
                } else {
                    $('#err_JAN').html("対象のJANコード商品は作業中です。");
                    $('#get_JAN').val("");
                    $('#get_JAN').focus();
                }

            });


            $('table tbody').on('click', 'tr', function() {
                var row = $(this).closest('tr');
                var Shouhin_num = row.find('td:eq(1)').text().trim();
                var Shouhin_name = row.find('td:eq(4)').text().trim();
                var shipping_moto_name = row.find('td:eq(5)').text().trim();

                console.log("Shouhin_num");
                console.log("Shouhin_name");
                console.log("shipping_moto_name");

                // 取得した値を詳細画面へ渡して遷移
                window.location.href = 'detail.php?Shouhin_num=' + Shouhin_num + '&Shouhin_name=' + Shouhin_name + '&shipping_moto_name=' + shipping_moto_name;
            });

            // 「更新」ボタンを押した時の処理
            $('#Kousin_Btn').on('click', function() {
                location.reload();
            });


            // 「全表示ボタン」押したら
            $('#all_select_btn').on('click', function() {

                var selectedDay = '<?php echo $select_day; ?>';
                var select_souko_code = '<?php echo $select_souko_code; ?>';
                var get_unsou_name = '<?php echo $get_unsou_name; ?>';
                var select_unsou_code = '<?php echo $select_unsou_code; ?>';
                var get_souko_name = '<?php echo $get_souko_name; ?>';

                // 複数選択 2024/06/07
                var fukusuu_unsouo_num = "<?php echo isset($fukusuu_unsouo_num) ? $fukusuu_unsouo_num : ''; ?>";
                // 2024/06/12
                var fukusuu_select = "<?php echo isset($fukusuu_select) ? $fukusuu_select : ''; ?>";
                var fukusuu_select_val = "<?php echo isset($fukusuu_select_val) ? $fukusuu_select_val : ''; ?>";
                // 2024/06/12
                var select_toki_code = "<?php echo isset($selectedToki_Code) ? $selectedToki_Code : ''; ?>";


                // 選択条件分岐 2024/06/07
                if (fukusuu_select != "") {
                    var show_all_flg = 2;
                    var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_unsouo_num + '&fukusuu_select=' + fukusuu_select + '&fukusuu_select_val=' + fukusuu_select_val + '&show_all_flg=' + show_all_flg;
                } else if (select_toki_code != "") {
                    var show_all_flg = 1;
                    var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&show_all_flg=' + show_all_flg;
                } else {
                    var show_all_flg = 0;
                    var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&show_all_flg=' + show_all_flg;
                }

                window.location.href = url;

            });


        });
    </script>

    <script>
        $('tr[data-href]').click(function() {
            var href = $(this).data('href');

            console.log("リンク値:::" + href);
            window.location.href = href;
        });
    </script>


</body>

</html>
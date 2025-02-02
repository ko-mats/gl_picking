<?php

ini_set('display_errors', 1);

require(dirname(__FILE__) . "/class/function.php");

// セッションスタート
session_start();

// セッションIDが一致しない場合はログインページにリダイレクト
if (!isset($_SESSION["sid"])) {
    header("Location: index.php");
    exit;
}

// === ログイン ID
if (isset($_SESSION['input_login_id'])) {
    $input_login_id = $_SESSION['input_login_id'];
}

//出荷指示日時があるかどうかの判定
$GET_unsou_Flg = 200;
if (isset($_GET['unsou_Flg'])) {

    $unsou_Flg = $_GET['unsou_Flg'];
    $error_day = $_GET['error_day'];

    if ($unsou_Flg == 0) {
        $GET_unsou_Flg = 0;
    }
}

// セッションハイジャック対策 
session_regenerate_id(TRUE);
// ************ 二重送信防止用トークンの発行 ************
$token_jim = uniqid('', true);
//トークンをセッション変数にセット
$_SESSION['token_jim'] = $token_jim;


$selected_day = '';
if (isset($_GET['back_six']) && $_GET['back_six'] === 'ok') {
    $selected_day = $_GET['day'];
} 

?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- css -->
    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/six.css">

    <link href="./css/all.css" rel="stylesheet">

    <!-- jQuery UI -->
    <link rel="stylesheet" href="./css/jquery-ui.min.css">

    <!-- jQuery cdn -->
    <script src="./js/jquery.min.js"></script>
    
    <title>ピッキング実績照会出荷日指定</title>
</head>

<body>

    <div class="head_box">
        <div class="head_content">
            <span class="home_icon_span">
                <a href="./top_menu.php"><img src="./img/home_img.png"></a>
            </span>

            <span class="App_name">
                グリーンライフ ピッキング
            </span>
        </div>
    </div>


    <div class="head_box_02">
        <div class="head_content_02">
            <span class="home_sub_icon_span">
                <a href="#"><img src="./img/page_img.png"></a>
            </span>

            <span class="page_title">
                出荷日選択(ピッキング実績照会)
            </span>
        </div>
    </div>

    <div class="container" id="app">
        <div class="content_top" id="six_content">

            <input name="day_val" type="text" id="datepicker" class="text_box_tpl_01" value="">

            <div class="btn_01" id="day_search_submit_box">
                <button id="day_search_submit" class="button_01" type="button" @click="submitForm">開始</button>
            </div>

            <div class="error-message" v-show="error">日付を入力してください。</div>

            <?php if ($GET_unsou_Flg == 0) : ?>
                <p style="color:red;">
                    出荷指示されていません。
                </p>
            <?php endif; ?>

        </div>

        
    </div> <!-- ================ END container =============== -->    

    <div>
        <!-- フッターメニュー -->
        <footer class="footer-menu_fixed">
            <ul>
                <?php $back_flg = 1; ?>
                <?php $url = "./top_menu.php?back_menu=ok&id=" . $input_login_id; ?>
                <li><a href="<?php print h($url); ?>">戻る</a></li>
                <li><a href="./six.php">更新</a></li>
            </ul>
        </footer>
    </div>



    <!-- jQuery UI -->
    <script src="./js/jquery-ui.min.js"></script>

    <script type="text/javascript">
        (function($) {

            $(document).ready(function() {

                $("#datepicker").datepicker({
                    buttonText: "日付を選択",
                    showOn: "both",
                    onSelect: function(selectedDate) {
                        app.dayval = selectedDate; // 選択された日付をVueのdataに反映
                    }
                });


                $.datepicker.regional['ja'] = {
                    closeText: '閉じる',
                    prevText: '<前',
                    nextText: '次>',
                    currentText: '今日',
                    monthNames: ['1月', '2月', '3月', '4月', '5月', '6月',
                        '7月', '8月', '9月', '10月', '11月', '12月'
                    ],
                    monthNamesShort: ['1月', '2月', '3月', '4月', '5月', '6月',
                        '7月', '8月', '9月', '10月', '11月', '12月'
                    ],
                    dayNames: ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'],
                    dayNamesShort: ['日', '月', '火', '水', '木', '金', '土'],
                    dayNamesMin: ['日', '月', '火', '水', '木', '金', '土'],
                    weekHeader: '週',
                    dateFormat: 'yy年mm月dd日',
                    firstDay: 0,
                    isRTL: false,
                    showMonthAfterYear: true,
                    yearSuffix: '年'
                };
                $.datepicker.setDefaults($.datepicker.regional['ja']);

            });

            var login_id = "<?php echo isset($input_login_id) ? $input_login_id : ''; ?>";
            var get_unsou_flg = "<?php echo isset($GET_unsou_Flg) ? $GET_unsou_Flg : ''; ?>";
            if (login_id != "" && get_unsou_flg != 0) {
                // 現在の日付を取得
                var today = new Date();
                var day = String(today.getDate()).padStart(2, '0');
                var month = String(today.getMonth() + 1).padStart(2, '0'); // 月は0から始まるので+1する
                var year = today.getFullYear();
                // フォームに現在の日付を設定
                var formattedDate = year + '年' + month + '月' + day + '日';
                $("#datepicker").val(formattedDate);
            }

            var back_date = "<?php echo isset($selected_day) ? $selected_day : ''; ?>";
            if (back_date != "") {
                // 日付文字列をDateオブジェクトに変換
                var date = new Date(back_date);

                // 年、月、日を取得
                var year = date.getFullYear();
                var month = String(date.getMonth() + 1).padStart(2, '0'); // 月は0から始まるため+1
                var day = String(date.getDate()).padStart(2, '0');

                // フォームに現在の日付を設定
                var formattedDate = year + '年' + month + '月' + day + '日';
                $("#datepicker").val(formattedDate);
            }

            var error_day = "<?php echo isset($error_day)? $error_day : ''; ?>";
            if (error_day != "") {
                // 日付文字列をDateオブジェクトに変換
                var date = new Date(error_day);

                // 年、月、日を取得
                var year = date.getFullYear();
                var month = String(date.getMonth() + 1).padStart(2, '0'); // 月は0から始まるため+1
                var day = String(date.getDate()).padStart(2, '0');

                // フォームに現在の日付を設定
                var formattedDate = year + '年' + month + '月' + day + '日';
                $("#datepicker").val(formattedDate);

            }

            


            $("#day_search_submit").click(function() {
                var selectedDate = $("#datepicker").val();
                // 日付をYYYY/MM/DD形式に整形
                var formattedDate = selectedDate.replace(/年|月/g, '-').replace(/日/g, '');

                if (selectedDate.trim() === '') {
                    $(".error-message").show();
                } else {
                    $(".error-message").hide();
                    var url = "./seven.php?selected_day=" + encodeURIComponent(formattedDate);
                    // リダイレクト
                    window.location.href = url;
                }
            });

        })(jQuery);
    </script>

</body>

</html>
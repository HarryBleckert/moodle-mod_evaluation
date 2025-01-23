<?php
// require_once("styles.css");
// <!--link rel = "stylesheet" type = "text/css"  href = "https://moodle.ash-berlin.eu:88/mod/evaluation/styles.css"-- >


// required for printing with comfortable width
if (!isset($printWidth)) {
    $printWidth = "100vw";
}

if (!isset($showGraf)) {
    $showGraf = "";
}

$hide_graphic_data = ev_get_string('hide_graphic_data');
$show_graphic_data = ev_get_string('show_graphic_data');

// required for LoginAs
$hide = "";
if (!empty($_SESSION["LoggedInAs"])) {
    if (substr($CFG->release, 0, 1) < "4") // Moodle Version <4
    {
        $hide = 'document.getElementById("page-footer").style.display="none";
        var nav = document.getElementsByClassName("footnote");
        for (var i = 0; i < nav.length; i++) {	nav[i].style.display="none"; };';
        //document.getElementsByClassName("logininfo")[0].style.display="none";
    } else {
        $hide = 'var nav = document.getElementsByClassName("usermenu");
        for (var i = 0; i < nav.length; i++) {	nav[i].style.display="none"; };';
    }
}
//handle show data and print width
?>


< style > @media print { @page
    {size: auto;}
}
</style>


<script>


    var addFunctionOnWindowLoad = function(callback)
    {if (window.addEventListener )
    {window.addEventListener('load',callback,false);}
        else {window.attachEvent('onload',callback);}
    }

    <?php echo $hide; ?>

    // toggle show graphics data
    var showGraf = "<?php echo $showGraf;?>";
    //console.log("showGraf: ",showGraf);
    //alert("showGraf: "+showGraf);
    var printWidth = "<?php echo $printWidth;?>";
    //alert("printWidth: "+printWidth);

    if ( showGraf !== '' )
    {require(["jquery"], function ($) {

        if (showGraf == "true") {
            $("[id^=chart-table-data-]").show();
            $("[aria-controls^=chart-table-data-]").text('<?php echo $hide_graphic_data;?>');
            $("[aria-controls^=chart-table-data-]").attr("aria-expanded", true);
        } else {
            $("[id^=chart-table-data-]").hide();
            $("[aria-controls^=chart-table-data-]").text('<?php echo $show_graphic_data;?>');
            $("[aria-controls^=chart-table-data-]").attr("aria-expanded", false);
        }
    });
    }


    // print function
    function printPage()
    {	///window.print(false);
        window.print();
        //return false;  //not working with Firefox
        //return;
    }


    //Fix chartjs printing: (problem with Frefox, right aligned)
    window.onbeforeprint = (ev) => {
    for (var id in Chart.instances)
{let instance = Chart.instances[id];
    let b64 = instance.toBase64Image();
    let i = new Image();
    i.style.maxWidth = printWidth;
    i.src = b64;
    let parent = instance.canvas.parentNode;
    instance.tempImage = i;
    instance.oldDisplay = instance.canvas.style.display;
    instance.canvas.style.display = "none";
    parent.appendChild(i);
};

    require(["jquery"], function($)
{$("[aria-controls^=chart-table-data-]").text("");
    $("[aria-controls^=chart-table-data-]").hide();
    //$(".chart-table-expand").hide();
    $(".activity-navigation").hide();
    $(".footnote").hide();
    $("#page-footer").hide();
});
    ;
};

    window.onafterprint = (ev) => {
    for (var id in Chart.instances) {
    let instance = Chart.instances[id];
    if (instance.tempImage) {
    let parent = instance.canvas.parentNode;
    parent.removeChild(instance.tempImage);
    instance.canvas.style.display = instance.oldDisplay;
    delete instance.oldDisplay;
    delete instance.tempImage;
}
};
    require(["jquery"], function($) {
    showAnker = '<?php echo $show_graphic_data;?>';
    if ( showGraf == "true")
    {showAnker = '<?php echo $hide_graphic_data;?>';}
    $("[aria-controls^=chart-table-data-]").text(showAnker);
    $("[aria-controls^=chart-table-data-]").show();
    //$(".chart-table-expand").show();
    $(".activity-navigation").show();
    $(".footnote").show();
    $("#page-footer").show();
});
    ;
};


    // not yet implemented 2022-02-13
    function showgraf( showGraf )
    {require(["jquery"], function ($) {

        // toggle show graphics data
        showGraf = !showGraf;
        //console.log("showGraf: ",showGraf);

        if (showGraf == "true") {
            $("[id^=chart-table-data-]").show();
            $("[aria-controls^=chart-table-data-]").text('<?php echo $hide_graphic_data;?>');
            $("[aria-controls^=chart-table-data-]").attr("aria-expanded", true);
        } else {
            $("[id^=chart-table-data-]").hide();
            $("[aria-controls^=chart-table-data-]").text('<?php echo $show_graphic_data;?>');
            $("[aria-controls^=chart-table-data-]").attr("aria-expanded", false);
        }
    });;
    }


    /*
    // unused snippets


    if ( typeof Chart !== 'undefined' )
    {for (var id in Chart.instances)
    {let instance = Chart.instances[id];
        if ( instance.type == "line" )
    {instance.line.borderWidth = 2;
        instance.point.borderWidth = 1;
        instance.point.radius = 12;
        //Chart.defaults.global.elements.point.borderWidth = 1;
        instance.point.hoverRadius = 6;
        instance.point.pointStyle = "circle";
    }
    }
    }
    */

</script>

<?php


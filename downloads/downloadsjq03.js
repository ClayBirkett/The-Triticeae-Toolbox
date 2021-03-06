/*global, alert, jQuery, Ajax, $ */

var php_self = document.location.href;
var title = document.title;
var map = 1;

function select_map() {
    jQuery( "#select-map" ).dialog( "open" );
}

function define_terms() {
    jQuery( "#define-terms" ).dialog( "open" );
}

function select_download(click_id) {
  var typeP=document.getElementById('typeP');
  var typeG=document.getElementById('typeG');
  var typeGE=document.getElementById('typeGE');
  if (click_id == "typeG") {
      typeGE.checked = false;
  } else if (click_id == "typeGE") {
      typeG.checked = false;
  }
  document.getElementById('step6').innerHTML = "";
  jQuery.ajax({
    type: "GET",
    url: php_self,
    data: "function=verifyLines&typeP=" + typeP.checked + "&typeG=" + typeG.checked + "&typeGE=" + typeGE.checked,
    success: function(data, textStatus) {
      jQuery("#step5").html(data);
      if (typeG.checked) {
        load_markers_lines( mm, mmaf);
      } else if (typeGE.checked) {
        load_markers_lines( mm, mmaf);
      } else {
        load_pheno_lines();
      }
    },
    error: function() {
        alert('Error in selecting download');
    }
  });
}

jQuery(function() {
  jQuery( "#select-map" ).dialog({
      autoOpen: false,
      height: 450,
      width: 550,
      modal: false,
      buttons: {
        Close: function() {
          jQuery( this ).dialog( "close" );
          location.reload();
        }
      }
  });
  jQuery( "#define-terms" ).dialog({
      autoOpen: false,
      height: 350,
      width: 550,
      modal: false,
      open: function(event, ui) {
        jQuery("button:contains('Close')").focus();
      },
      buttons: {
        Close: function() {
          jQuery( this ).dialog( "close" );
        }
      }
    });
});

function save_map() {
  var i;
  for (i=0; i<document.myForm.map.length; i++) {
      if (document.myForm.map[i].checked===true) {
          map = document.myForm.map[i].value;
      }
  }
  jQuery.ajax({
    type: "GET",
    url: "maps/select_map.php",
    data: "map=" + map + "&function=Save",
    success: function(data, textStatus) {
        jQuery("#select-map2").html(data);
    },
    error: function() {
        alert('Error in selecting design type');
      }
  });

}


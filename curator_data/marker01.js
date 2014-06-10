/*global $,Ajax,Element,window,document,alert*/

var php_self = document.location.href;
var title = document.title;
var overwrite = "";
var orderAllele = "";
var expand = "";

function update_databaseSNP(filepath, filename, username, filetype) {
     overwrite = 0;
     if (document.getElementById('use_imp').checked) {
                overwrite = 1;
     }
     var url = php_self + "?function=typeDatabaseSNP&linedata=" + filepath + "&file_name=" + filename + "&user_name=" + username + "&file_type=" + filetype + "&overwrite=" + overwrite + "&orderAllele=" + orderAllele;
     // Opens the url in the same window
     window.open(url, "_self");
}
        
function CheckSynonym(filepath, filename, username, filetype) {
     overwrite = 0;
     if (document.getElementById('use_imp').checked) {
        overwrite = 1;
     }
     orderAllele = 0;
     if (document.getElementById('order_yes').checked) {
         orderAllele = 1;
     }
     $('update').show();
     var url = php_self + "?function=typeCheckProgress&linedata=" + filepath + "&file_name=" + filename;
     var tmp1 = new Ajax.Updater($('update'), url, {
     });

     $('checksyn').hide();
     url = php_self + "?function=typeCheckSynonym&linedata=" + filepath + "&file_name=" + filename + "&user_name=" + username + "&file_type=" + filetype + "&overwrite=" + overwrite + "&expand=" + expand + "&orderAllele=" + orderAllele;
     var tmp2 = new Ajax.Updater($('checksyn'), url, {
         onCreate: function() { Element.show('spinner'); },
         onSuccess : function() {
           $('checksyn').show();
           Element.hide('spinner');
           Element.hide('update');
         },
         onFailure : function() {
           Element.hide('spinner');
           Element.hide('update');
           alert('Internal Server Error, possibly out of memory');
         }
     });
}

function disp_index(pheno_uid) {
    var off_switch = "off_switch" + pheno_uid;
    var on_switch = "on_switch" + pheno_uid;
    var content1 = "content1" + pheno_uid;
    var content2 = "content2" + pheno_uid;
    expand = 1;
    Element.hide(on_switch);
    Element.show(off_switch);
    Element.hide(content1);
    Element.show(content2);
}

function hide_index(pheno_uid) {
    var off_switch = "off_switch" + pheno_uid;
    var on_switch = "on_switch" + pheno_uid;
    var content1 = "content1" + pheno_uid;
    var content2 = "content2" + pheno_uid;
    expand = 0;
    Element.hide(off_switch);
    Element.show(on_switch);
    Element.show(content1);
    Element.hide(content2);
}


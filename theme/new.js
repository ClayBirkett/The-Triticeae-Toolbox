/*global $,Ajax,window*/

var isIE = /*@cc_on!@*/false;
var title = document.title;

function setup(){}

function getElmt(id)
{
	if (isIE) { return (document.all[id]); }
	else { return (document.getElementById(id)); }
}

function moveQuickLinks()
{
	var quickLinks = getElmt("quicklinks");
	var pos = 0;
	if (document.documentElement) { pos = 15 + document.documentElement.scrollTop; }
	else { pos = 15 + document.body.scrollTop; }
	if (pos < 141) { pos = 141; }
	quickLinks.style.top = pos + "px";
	setTimeout(moveQuickLinks, 0);
}
setTimeout(moveQuickLinks, 2000);

function set_over() {
    this.className = "over";
}
function set_blank() {
    this.className = '';
}

var startList = function() {
    if (document.all && document.getElementById) {
        var navRoot = document.getElementById("nav");
        var i;
        var node;
        for (i = 0; i < navRoot.childNodes.length; i++) {
            node = navRoot.childNodes[i];
            if (node.nodeName == "LI") {
                node.onmouseover = set_over();
                node.onmouseout = set_blank();
            }
        }
    }
};

function update_side_menu() {
    var url = "side_menu.php";
    var tmp = new Ajax.Updater($('quicklinks'), url, {
        onComplete : function() {
            $('quicklinks').show();
            document.title = title;
        }
    });
}

window.onload = startList;



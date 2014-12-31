var getLocation = function(href) {
    var l = document.createElement("a");
    l.href = href;
    return l;
};

var laravelBBcheckIfUrlValid = function (value) {
    return /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value);
}
var makeLaravelBBUrlSafe = function (url) {
    var output;
    if (laravelBBcheckIfUrlValid(url)){
        var parsed=getLocation(url);
        if(parsed.host==laravelBBhost){
            console.log("match parsed:"+parsed.host);
            output= laravelBBhost + "/BBBase64/" + Base64.encode(laravelBBthisPageaddress + url);
        }else{
            console.log("not match parsed:"+parsed.host);
            output= laravelBBhost + "/BBBase64/" + Base64.encode(url);
        }
    }else {
        output= laravelBBhost + "/BBBase64/" + Base64.encode(laravelBBthisPageaddress + url);
    }
    console.log("before: "+url+"\nafter:"+output);
    return output;
}
var elementsHaveSrcCount=0;
var laravelBBSetUrlSafer=function(){
    var elementsHaveSrc=document.querySelectorAll("*[src]");
    if(elementsHaveSrc.length>0){
        for(var i=0;i<elementsHaveSrc.length;i++){
            if (elementsHaveSrc[i].src.indexOf(laravelBBhost+"/BBBase64/") !== 0 && elementsHaveSrc[i].src.indexOf(laravelBBhost+"/encryptURL/") !== 0){
                elementsHaveSrc[i].src = makeLaravelBBUrlSafe(elementsHaveSrc[i].src);
            }
        }
    }
}
if (!Object.prototype.watch) {
	Object.defineProperty(Object.prototype, "watch", {
		  enumerable: false
		, configurable: true
		, writable: false
		, value: function (prop, handler) {
			var
			  oldval = this[prop]
			, newval = oldval
			, getter = function () {
				return newval;
			}
			, setter = function (val) {
				oldval = newval;
				return newval = handler.call(this, prop, oldval, val);
			}
			;
			
			if (delete this[prop]) { // can't watch constants
				Object.defineProperty(this, prop, {
					  get: getter
					, set: setter
					, enumerable: true
					, configurable: true
				});
			}
		}
	});
}
// object.unwatch
if (!Object.prototype.unwatch) {
	Object.defineProperty(Object.prototype, "unwatch", {
		  enumerable: false
		, configurable: true
		, writable: false
		, value: function (prop) {
			var val = this[prop];
			delete this[prop]; // remove accessors
			this[prop] = val;
		}
	});
}
var t=document.querySelectorAll("*[src]");

for(var i=0;i<t.length;i++){
    if (t[i].addEventListener) { // all browsers except IE before version 9
	t[i].addEventListener ('DOMAttrModified', OnAttrModified, false);    // Firefox, Opera, IE
    }
    if (t[i].attachEvent) {  // Internet Explorer and Opera
	t[i].attachEvent ('onpropertychange', OnAttrModified);   // Internet Explorer
    }
}
var checkLoad = function() {
    laravelBBSetUrlSafer();
    setTimeout(checkLoad,10);
}
function OnAttrModified (event) {
	var message = "";
	if ('attrChange' in event) {    // Firefox, Opera, Internet Explorer from version 9
	    message += "Something has happened to an attribute of the " + event.target.tagName + " element.\n";
	    switch (event.attrChange) {
	    case MutationEvent.MODIFICATION:
		message += "The value of the " + event.attrName + " attribute has been changed from "
			    + event.prevValue + " to " + event.newValue + ".";
		break;
	    case MutationEvent.ADDITION:
		message += "The " + event.attrName + " attribute has been added to the element "
			    + "with the value of " + event.newValue + ".";
		break;
	    case MutationEvent.REMOVAL:
		message += "The " + event.attrName + " attribute has been removed from the element."
			    + "The value was " + event.prevValue + " previously.";
		break;
	    };
	}

	if ('propertyName' in event) {  // Internet Explorer
	    message = "The " + event.propertyName + " property of the "
			+ event.srcElement.tagName + " element has been changed.";
	}

	console.log(message);
}

//checkLoad();
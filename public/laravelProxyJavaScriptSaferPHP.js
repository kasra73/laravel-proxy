var laravelBBgetLocation = function (href) {
	var l = document.createElement("a");
	l.href = href;
	return l;
}
var laravelBBcheckIfUrlValid = function (value) {
	return /^(https?|ftps?):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value);
}
var makeLaravelBBUrlSafe = function (url) {
	if (url.indexOf(laravelBBhost + "/BBBase64/") === 0 || url.indexOf(laravelBBhost + "/encryptURL/") === 0 || url.indexOf("#") === 0 || url.indexOf("javascript:") === 0) {
		return url;
	}
	if (url.indexOf("//") === 0) {
		url = "https:" + url;
	}
	var output;
	if (laravelBBcheckIfUrlValid(url)) {
		var parsed = laravelBBgetLocation(url);
		if (parsed.host == laravelBBhost) {
			output = laravelBBhost + "/BBBase64/" + Base64.encode(laravelBBthisPageaddress + url);
		} else {
			output = laravelBBhost + "/BBBase64/" + Base64.encode(url);
		}
	} else {
		output = laravelBBhost + "/BBBase64/" + Base64.encode(laravelBBthisPageaddress + url);
	}
	return output;
}
laravelBBSRCencoder = function (element, type, value) {
    if (type == 'src') {
        if (element instanceof HTMLElement)
            return element.src = makeLaravelBBUrlSafe(value);
        return element.src = value;
    }
    if (type == 'location') {
        if (element instanceof Window) {
            return element.location = makeLaravelBBUrlSafe(value);
        }
        return element.location = value;
    }
    if (type == 'cookie') {
        if (element instanceof Document) {
            console.log("cookie not set: "+value+" ");
            return false;
            //return element.cookie = makeLaravelBBUrlSafe(value);
        }
        return element.cookie = value;
    }
    if (type == 'replace') {
        if (element instanceof Location) {
            return element.replace(makeLaravelBBUrlSafe(value));
        }
        return element.replace(value);
    }

}
laravelBB_XMLOpen=XMLHttpRequest.prototype.open;
XMLHttpRequest.prototype.open = function (m, u, b) {
    laravelBB_XMLOpen.call(this,m,makeLaravelBBUrlSafe(u),b);
}
laravelBB_SafeNode = function (node) {
    //console.log("Before: "+node);
    var list = node.childNodes;
    for (var i = 0; i < list.length; i++) {
        if ((list[i] instanceof HTMLElement)||(list[i] instanceof DocumentFragment))
            laravelBB_SafeNode(list[i]);
    }
    if (node.hasAttribute('src')) {
        node.src = makeLaravelBBUrlSafe(node.src);
    }
    if (node.hasAttribute('href')) {
        node.href = makeLaravelBBUrlSafe(node.href);
    }
    if (node instanceof HTMLFormElement) {
        if (node.hasAttribute('action')) {
            node.action = makeLaravelBBUrlSafe(node.action);
        }
    }
    //console.log("After: "+node);
    return node;
}
laravelBB_old_appendChild = Element.prototype.appendChild;
Element.prototype.appendChild = function (node) {
	if ((node instanceof HTMLElement)||(node instanceof DocumentFragment)) {
		node = laravelBB_SafeNode(node);
	} else {
		return laravelBB_old_appendChild.call(this, node);
	}
	return laravelBB_old_appendChild.call(this, node);
}

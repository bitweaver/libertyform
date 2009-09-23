// xSelect r4, Copyright 2004-2007 Michael Foster (Cross-Browser.com)
// Mods by Daniel Sutcliffe 2009 to make work better within bitweaver
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSelect(sId, fnSubOnChange, sMainName, sSubName, bUnder, iMargin) // Object Prototype
{
  function s1OnChange()
  {
    var io, s2 = this.xSelSub; // 'this' points to s1
    s2.disabled = true;
    // clear existing
    for(io=0; io<s2.options.length; ++io) {
      s2.options[io] = null;
    }
    // insert new
    var a = this.xSelData, ig = this.selectedIndex;
    for(io=1; io<a[ig].length; ++io) {
      s2.options[io-1] = a[ig][io];
      s2.options[io-1].selected = (a[ig][0].subsel == io);
      if(a[ig][io].value != 0) {
        s2.disabled = false;
      }
    }
  }

  // Check for required browser objects
  var s0 = xGetElementById(sId);
  if(!s0 || !s0.firstChild || !s0.nodeName || !document.createElement || !s0.form || !s0.form.appendChild) {
    return null;
  }
  // Create main category SELECT element
  var s1 = document.createElement('SELECT');
  s1.id = s1.name = sMainName ? sMainName : sId + '_main';
  s1.display = 'block'; // for opera bug?
  s1.style.position = 'absolute';
  s1.xSelObj = this;
  s1.xSelData = new Array();
  var ig=0, io, op, og, ogsel, a = s1.xSelData;
  og = s0.firstChild;
  while(og) {
    if(og.nodeName.toLowerCase() == 'optgroup') {
      a[ig] = new Array();
      a[ig][0] = new Object();
      a[ig][0].label = og.label;
      a[ig][0].subsel = 0;
      io = 0;
      op = og.firstChild;
      while(op) {
        if(op.nodeName.toLowerCase() == 'option') {
          io++;
          a[ig][io] = op;
          if(op.selected == true) {
            ogsel = ig;
            a[ig][0].subsel = io;
          }
        }
        op = op.nextSibling;
      }
      ig++;
    }
    og = og.nextSibling;
  }
  // No optgroups in this select so don't do anything
  if(a.length == 0) {
    return null;
  }
  // append s1 to s0's parent - this makes sure new selects are within same object/div as original select
  s0.parentNode.appendChild(s1);
  // In s1 insert a new OPTION for each OPTGROUP in s0
  for(ig=0; ig<a.length; ++ig) {
    op = new Option(a[ig][0].label);
    if(ig == ogsel) op.selected = true;;
    s1.options[ig] = op;
  }
  // Create sub-category SELECT element
  var s2 = document.createElement('SELECT');
  s2.id = sSubName ? sSubName : sId + '_sub';
  s2.name = s0.name;
  s2.disabled = true;
  s0.name = 'old_' + s2.name;
  s2.display = 'block'; // for opera bug?
  s2.style.position = 'absolute';
  s2.xSelMain = s1;
  s1.xSelSub = s2;
  // Append s2 to s0's parent
  s0.parentNode.appendChild(s2);
  // Add event listeners
  s1.onchange = s1OnChange;
  s2.onchange = fnSubOnChange || null;
  // Hide s0. Position and show s1 where s0 was.
  s0.style.visibility = 'hidden';
  s0pos = getElementAbsolutePos(s0);
  xMoveTo(s1, s0pos.x, s0pos.y);
  s1.style.visibility = 'visible';
  iMargin = iMargin || 0;
  if(bUnder) { // Position s2 under s1.
    xMoveTo(s2, s0pos.x, s0pos.y + xHeight(s1) + iMargin);
  } else { // Position s2 to the right of s1.
    xMoveTo(s2, s0pos.x + xWidth(s1) + iMargin, s0pos.y);
  }
  // Initialize s2 by faking an s1 change.
  s1.onchange();
}

function xSelectMulti(sId, pId) {
  var re = RegExp(sId, 'i');
  selects = xGetElementsByTagName('select', pId);
  for(i=0; i<selects.length; i++) {
    if((selects[i].id).search(re) != -1) {
      xSelect(selects[i].id);
    }
  }
}

var __isIE =  navigator.appVersion.match(/MSIE/);
var __userAgent = navigator.userAgent;
var __isFireFox = __userAgent.match(/firefox/i);
var __isFireFoxOld = __isFireFox && 
   (__userAgent.match(/firefox\/2./i) || __userAgent.match(/firefox\/1./i));
var __isFireFoxNew = __isFireFox && !__isFireFoxOld;

function __parseBorderWidth(width) {
  var res = 0;
  if((typeof(width) == "string") && (width != null) && (width != "")) {
    var p = width.indexOf("px");
    if(p >= 0) {
      res = parseInt(width.substring(0, p));
    } else {
      // Do not know how to calculate other values (such as 0.5em or
      // 0.1cm) correctly now so just set the width to 1 pixel
      res = 1; 
    }
  }
  return res;
}

//returns border width for some element
function __getBorderWidth(element) {
  var res = new Object();
  res.left = 0; res.top = 0; res.right = 0; res.bottom = 0;
  if(window.getComputedStyle) { // for Firefox
    var elStyle = window.getComputedStyle(element, null);
    res.left = parseInt(elStyle.borderLeftWidth.slice(0, -2));
    res.top = parseInt(elStyle.borderTopWidth.slice(0, -2));
    res.right = parseInt(elStyle.borderRightWidth.slice(0, -2));
    res.bottom = parseInt(elStyle.borderBottomWidth.slice(0, -2));
  } else { // for other browsers
    res.left = __parseBorderWidth(element.style.borderLeftWidth);
    res.top = __parseBorderWidth(element.style.borderTopWidth);
    res.right = __parseBorderWidth(element.style.borderRightWidth);
    res.bottom = __parseBorderWidth(element.style.borderBottomWidth);
  }
  return res;
}

// returns absolute position of some element within document
function getElementAbsolutePos(element) {
  var res = new Object();
  res.x = 0; res.y = 0;
  if(element !== null) {
    res.x = element.offsetLeft;
    res.y = element.offsetTop;
    var offsetParent = element.offsetParent;
    var parentNode = element.parentNode;
    var borderWidth = null;

    while(offsetParent != null) {
      res.x += offsetParent.offsetLeft;
      res.y += offsetParent.offsetTop;
      var parentTagName = offsetParent.tagName.toLowerCase();
      if((__isIE && parentTagName != "table") ||
         (__isFireFoxNew && parentTagName == "td")) {
        borderWidth = __getBorderWidth(offsetParent);
        res.x += borderWidth.left;
        res.y += borderWidth.top;
      }

      if(offsetParent != document.body && 
        offsetParent != document.documentElement) {
        res.x -= offsetParent.scrollLeft;
        res.y -= offsetParent.scrollTop;
      }

      // next lines are necessary to support FireFox problem with offsetParent
      if(!__isIE) {
        while(offsetParent != parentNode && parentNode !== null) {
          res.x -= parentNode.scrollLeft;
          res.y -= parentNode.scrollTop;
          if(__isFireFoxOld) {
                        borderWidth = __getBorderWidth(parentNode);
                        res.x += borderWidth.left;
                        res.y += borderWidth.top;
          }
          parentNode = parentNode.parentNode;
        }
      }
      parentNode = offsetParent.parentNode;
      offsetParent = offsetParent.offsetParent;
    }
  }
  return res;
}

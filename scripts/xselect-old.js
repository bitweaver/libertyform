// xSelect r4, Copyright 2004-2007 Michael Foster (Cross-Browser.com)
// Mods by Daniel Sutcliffe 2009 to make work better within bitweaver
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSelect(sId, fnSubOnChange, sMainName, sSubName, bUnder, iMargin) // Object Prototype
{
  // Private Event Listener

  function s1OnChange()
  {
    var io, s2 = this.xSelSub; // 'this' points to s1
    //s2.style.visibility = 'hidden';
    s2.disabled = true;
    // clear existing
    for(io=0; io<s2.options.length; ++io) {
      s2.options[io] = null;
    }
    // insert new
    var a = this.xSelData, ig = this.selectedIndex;
    for(io=1; io<a[ig].length; ++io) {
      //op = new Option(a[ig][io]);
      //s2.options[io-1] = op;
      s2.options[io-1] = a[ig][io];
      if(a[ig][io].value != 0) {
        //s2.style.visibility = 'visible';
        s2.disabled = false;
      }
    }
  }

  // Constructor Code

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
  // append s1 to s0's parent - this makes sure new selects are within same object/div as original select
  s0.parentNode.appendChild(s1);
  // Iterate thru s0 and fill array.
  // For each OPTGROUP, a[og][0] == OPTGROUP label, and...
  // a[og][n] = innerHTML of OPTION n.
  var ig=0, io, op, og, ogsel, a = s1.xSelData;
  og = s0.firstChild;
  while(og) {
    if(og.nodeName.toLowerCase() == 'optgroup') {
      io = 0;
      a[ig] = new Array();
      a[ig][io] = og.label;
      op = og.firstChild;
      while(op) {
        if(op.nodeName.toLowerCase() == 'option') {
          io++;
          a[ig][io] = op;
          if(op.selected == true) ogsel = ig;
        }
        op = op.nextSibling;
      }
      ig++;
    }
    og = og.nextSibling;
  }
  // In s1 insert a new OPTION for each OPTGROUP in s0
  for(ig=0; ig<a.length; ++ig) {
    op = new Option(a[ig][0]);
    if(ig == ogsel) op.selected = true;;
    s1.options[ig] = op;
  }
  // Create sub-category SELECT element
  var s2 = document.createElement('SELECT');
  s2.id = sSubName ? sSubName : sId + '_sub';
  s2.name = s0.name;
  s2.disabled = true;
  //s2.style.visibility = 'hidden';
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
  xMoveTo(s1, s0.offsetLeft, s0.offsetTop);
  s1.style.visibility = 'visible';
  iMargin = iMargin || 0;
  if(bUnder) { // Position s2 under s1.
    xMoveTo(s2, s0.offsetLeft, s0.offsetTop + xHeight(s1) + iMargin);
  }
  else { // Position s2 to the right of s1.
    xMoveTo(s2, s0.offsetLeft + xWidth(s1) + iMargin, s0.offsetTop);
  }
  // Initialize s2
  s1.onchange();
}

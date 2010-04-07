// formfuncs.js - functions to deal with form input/validation/submission handling in LibertyForm
//
// First some jQuery extensions, requires jQuery 1.3+ loaded

// jQuery.ifChkdEnable() - typically used from onClick of a checkbox too enable or disable(&uncheck)
//   other checkbox dependent on whether the originating checkbox/radio is "checked".
//   Intended for boolack formfields
(function($){
  $.fn.extend({
    ifChkdEnable: function(chkbox) {
      return this.each(function() {
        if($(this).attr("checked")) {
          $(chkbox).each(function() { $(this).removeAttr("disabled"); });
        } else {
          $(chkbox).each(function() { $(this).removeAttr("checked"); $(this).attr("disabled","disabled"); });
        }
      });
    }
  });
})(jQuery); 

// jQuery.ifChkdShow() - typically used from onClick of a checkbox too show or hide another element
//   dependent on whether the originating checkbox/radio is "checked".  If animate param is true
//   then jQuery's slideuP/Down() is used otherwise hide/show(). The hide/show() animations aren't
//   used as they appear to be mostly broken in jQuery 1.3.2.
//   Intended for boolfield and radiodiv formfields
(function($){
  $.fn.extend({
    ifChkdShow: function(showelem, animate) {
      return this.each(function() {
        if($(this).attr("checked")) {
          $(showelem).each(function() {
            if(animate) {
              $(this).slideDown('fast');
            } else {
              $(this).show();
            }
          });
        } else {
          $(showelem).each(function() {
            if(animate) {
              $(this).slideUp('fast');
            } else {
              $(this).hide();
            }
          });
        }
      });
    }
  });
})(jQuery); 

// jQuery.givePrompt() - typically used from onBlur of a text input field to set he fields
//   value to promptTxt (and add the provided class) if it is empty.
(function($){
  $.fn.extend({
    givePrompt: function(promptTxt, promptingClass) {
      return this.each(function() {
        if($(this).val() == '') $(this).addClass(promptingClass).val(promptTxt);
      });
    }
  });
})(jQuery); 

// jQuery.losePrompt() - typically used from onFocus of a text input field to remove the fields
//   value (and the provided class) if it is equal to the promptTxt.
(function($){
  $.fn.extend({
    losePrompt: function(promptTxt, promptingClass) {
      return this.each(function() {
        if($(this).val() == promptTxt) $(this).val('').removeClass(promptingClass);
      });
    }
  });
})(jQuery); 

// Use LibertyForm namespace for the functions providing formfields javascript assistance
LibertyForm = {

// LibertyForm.setupBoolack() - given checkbox find partner checkbox that has an id of the originating
//   checkbox but with '_ack' appended to the end - initialize the pairing using above ifChkdEnable JQuery
//   extension and set up as onClick handler.
  "setupBoolack": function(boolack) {
    $(boolack).each(function() {
      var ackid = '#'+$(this).attr('id')+'_ack';
      $(this).ifChkdEnable(ackid).click(function() { $(this).ifChkdEnable(ackid); });
    });
  },

// LibertyForm.setupBoolfield() - given checkbox find partner div that has an id of the originating
//   checkbox but with '_fielddiv' appended to the end - initialize the pairing using above ifChkdShow JQuery
//   extension to not animate and set up as onClick handler to animate.
  "setupBoolfield": function(boolfield) {
    $(boolfield).each(function() {
      var fielddivid = '#'+$(this).attr('id')+'_fielddiv';
      var animate = !$(fielddivid).hasClass('noanimate');
      $(this).ifChkdShow(fielddivid, false).click(function() { $(this).ifChkdShow(fielddivid, animate); });
    });
  },

// LibertyForm.setupRadiodiv() - given radio button find partner div that has an id of the provided
//   trigger element but with '_fielddiv' appended to the end - initialize the pairing using above ifChkdShow
//   JQuery extension on the trigger to not animate and set up as onClick handler for all radio buttons to
//   animate show when trigger is checked and hide otherwise.
  "setupRadiodiv": function(radios, trigger) {
    var fielddivid = '#'+$(trigger).attr('id')+'_fielddiv';
    $(trigger).ifChkdShow(fielddivid, false);
    $(radios).click(function() { $(trigger).ifChkdShow(fielddivid, true); });
  },

// LibertyForm.setupFieldPrompt() - given a text field using the above give/losePrompt jQuery extensions
//   setup up that text field so that if it is empty it contains the given prompt text and class, then
//   while being 'used' it loses these, but will regain them if it ceases to be used and is still empty.
  "setupFieldPrompt": function (field, promptTxt, promptingClass) {
    $(field).givePrompt(promptTxt, promptingClass).blur(function() { $(this).givePrompt(promptTxt, promptingClass); });
    $(field).focus(function() { $(this).losePrompt(promptTxt, promptingClass); });
  },

// LibertyForm.setupFormFields() - give standard formfield javascript behavior defined here dependent on class names.
  "setupFormFields": function() {
    LibertyForm.setupBoolfield($('input.ff-boolfield'));
    LibertyForm.setupBoolack($('input.ff-boolack'));
  },

// LibertyForm.isEmail() - provided a string return boolean dependent on whether it could be a valid email address.
  "isEmail": function(etxt) {
    // First the basics, empty string, spaces, etc.
    if(!etxt || (etxt.indexOf(' ') != -1)) return false;

    // now split into local and domain part
    var atpos = etxt.lastIndexOf('@');
    if(atpos <= 0)  return false;

    // Check the domain part
    var dtxt = etxt.slice(atpos+1);
    if(!LibertyForm.isDomain(dtxt)) return false;

    // And finally check the local part
    var utxt = etxt.slice(0, atpos)
    if(!utxt.match(/^[0-9a-zA-Z\.\-\_\!\#\$\%\&\'\*\+\/\=\?\^\`\{\|\}\~]+$/)) return false;

    return true;
  },

// LibertyForm.isDomain() - provided a string return boolean dependent on whether it could be a valid domain name.
  "isDomain": function (dtxt) {
    if(!dtxt.match(/^([0-9a-zA-Z\-]+\.)+[a-zA-z]+$/)) return false;
    return true;
  }

}
// End of LibertyForm namespace

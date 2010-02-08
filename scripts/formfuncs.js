// formfuncs.js - a bunch of functions to deal with form input/validation/submission handling in LibertyForm
// requires jquery 1.3+ loaded

(function($){
  $.fn.extend({
    ifchkdEnable: function(chkbox) {
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

function setupBoolack(boolack) {
  $(boolack).each(function() {
    var ackid = '#'+$(this).attr('id')+'_ack';
    $(this).ifchkdEnable(ackid).click(function() { $(this).ifchkdEnable(ackid); });
  });
}

(function($){
  $.fn.extend({
    ifchkdShow: function(showelem, animate) {
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

function setupBoolfield(boolfield) {
  $(boolfield).each(function() {
    var fielddivid = '#'+$(this).attr('id')+'_fielddiv';
    $(this).ifchkdShow(fielddivid, false).click(function() { $(this).ifchkdShow(fielddivid, true); });
  });
}

function setupRadiodiv(radios, trigger) {
  var fielddivid = '#'+$(trigger).attr('id')+'_fielddiv';
  $(trigger).ifchkdShow(fielddivid, false);
  $(radios).click(function() { $(trigger).ifchkdShow(fielddivid, true); });
}

function losePrompt(field, promptTxt, promptingClass) {
  if($(field).val() == promptTxt) {
    $(field).val('').removeClass(promptingClass);
  }
}

function givePrompt(field, promptTxt, promptingClass) {
  if($(field).val() == '') {
    $(field).addClass(promptingClass).val(promptTxt);
  }
}

function setupFormFields() {
  setupBoolfield($('input.ff-boolfield'));
  setupBoolack($('input.ff-boolack'));
}

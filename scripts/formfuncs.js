// formfuncs.js - a bunch of functions to deal with form input/validation/submission handling in LibertyForm
// Will eventually need to be tidied up and probably moved elsewhere or integrated with other javascript libraries
function boolackFlip(boolfield) {
  if(ackfield = xGetElementById(boolfield.id+'_ack')) {
    ackfield.checked = false;
    ackfield.disabled = ((boolfield.checked) ? false : true);
  }
}
function boolfieldsFlip(boolfield) {
  if(divfield = xGetElementById(boolfield.id+'_fielddiv')) {
    divfield.style.display = ((boolfield.checked) ? 'block' : 'none');
  }
}
function newRadioFlip(triggerId) {
  subfieldsId = triggerId+'_fielddiv';
  if((trigger = xGetElementById(triggerId)) && (subfields = xGetElementById(subfieldsId))) {
    subfields.style.display = ((trigger.checked) ? 'block' : 'none');
  } else {
    alert("flip failed " + subfieldsId);
  }
}

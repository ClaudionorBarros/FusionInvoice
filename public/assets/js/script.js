/* Author: William G. Rivera*/

$(document).ready(function() {

    $(".dropdown-toggle").click(function(e) {
        var menu = $(this).next('.dropdown-menu'),
            mousex = e.pageX + 20, //Get X coodrinates
            mousey = e.pageY + 20, //Get Y coordinates
            menuWidth = menu.width(), //Find width of tooltip
            menuHeight = menu.height(), //Find height of tooltip
            menuVisX = $(window).width() - (mousex + menuWidth), //Distance of element from the right edge of viewport
            menuVisY = $(window).height() - (mousey + menuHeight); //Distance of element from the bottom of viewport

        if (menuVisX < 20) { //If tooltip exceeds the X coordinate of viewport
            // menu.css({'left': '-89px'});
        } if (menuVisY < 20) { //If tooltip exceeds the Y coordinate of viewport
            menu.css({
                'top': 'auto',
                'bottom': '100%',
            });
        }
    });

    $('html').click(function () {
        $('.dropdown-menu').removeAttr('style'); //Integrate this into the function. Try finding only the element is open and not all dropdown menus
    });

    // Handle click event for Email Template Tags insertion
    // Example Usage
    // <a href="#" class="text-tag" data-tag="{{{client_name}}}">Client Name</a>
    $('.text-tag').bind('click', function () {
        var templateTag = this.getAttribute("data-tag");
        insertAtCaret('email_template_body', templateTag);
        return false;
    });

    // Load Resize Function
    $(window).resize();

});

/*Fix Scrollbar on Main Content*/
$(window).resize(function(){
    var height = $(this).outerHeight() - $('nav.navbar').outerHeight() - $('.main-area .headerbar').outerHeight() - ($('.main-area .content').outerHeight() - $('.main-area .content').height()) - $('.nav-tabs').outerHeight();
    var width = $(this).outerWidth() - $('.sidebar').outerWidth();
    $('.main-area .container-fluid, .main-area .tab-content, .main-area .content, .main-area .table-content').height(height);
});

// Insert text into textarea at Caret Position
function insertAtCaret(areaId, text) {
    var txtarea = document.getElementById(areaId);
    var scrollPos = txtarea.scrollTop;
    var strPos = 0;
    var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
        "ff" : (document.selection ? "ie" : false));
    if(br == "ie") {
        txtarea.focus();
        var range = document.selection.createRange();
        range.moveStart('character', -txtarea.value.length);
        strPos = range.text.length;
    } else if(br == "ff") strPos = txtarea.selectionStart;

    var front = (txtarea.value).substring(0, strPos);
    var back = (txtarea.value).substring(strPos, txtarea.value.length);
    txtarea.value = front + text + back;
    strPos = strPos + text.length;
    if(br == "ie") {
        txtarea.focus();
        var range = document.selection.createRange();
        range.moveStart('character', -txtarea.value.length);
        range.moveStart('character', strPos);
        range.moveEnd('character', 0);
        range.select();
    } else if(br == "ff") {
        txtarea.selectionStart = strPos;
        txtarea.selectionEnd = strPos;
        txtarea.focus();
    }
    txtarea.scrollTop = scrollPos;
}

/**
 * Renders error messages into placeholder, styles the input fields. If errors is null, just hides the errors.
 * 
 * @param errors object in following format (Validator->getMessageBag()->toArray() returns this):
 *  ` "inputId": ["Error message."], "anotherInputId": ["Another error message."] ` or null
 * @param placeholder jQuery element where to put error messages
 */
function showErrors(errors, placeholder) {
    
    $('.control-group.error').removeClass('error');
    $(placeholder).html('');
    if (errors == null && placeholder) {
        return;
    }

    $.each(errors, function(id, message) {
        if (id) $('#' + id).parents('.control-group').addClass('error');
        if (placeholder) $(placeholder).append('<div class="alert alert-error">' + message[0] + '</div>');
    });

}
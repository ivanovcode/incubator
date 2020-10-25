function empty(data) {
    if(typeof(data) == 'number' || typeof(data) == 'boolean')
    {
        return false;
    }
    if(typeof(data) == 'undefined' || data === null)
    {
        return true;
    }
    if(typeof(data.length) != 'undefined')
    {
        return data.length == 0;
    }
    var count = 0;
    for(var i in data)
    {
        if(data.hasOwnProperty(i))
        {
            count ++;
        }
    }
    return count == 0;
}


$( document ).ready(function() {

    $('body').addClass('preloader-site');

    var path = window.location.protocol + "//" + window.location.host + "/" + window.location.pathname + window.location.search;
    var page = path.match(/([^\/]*)\/*$/)[1];
    console.log(page);
    $("a[href='" + page + "']").parent().addClass('active');

    var slideout = new Slideout({
        'panel': document.getElementById('panel'),
        'menu': document.getElementById('menu'),
        'padding': 256,
        'tolerance': 70
    });

    document.querySelector('.js-slideout-toggle').addEventListener('click', function() {
        slideout.toggle();
    });

    document.querySelector('.menu').addEventListener('click', function(eve) {
        if (eve.target.nodeName === 'A') { slideout.close(); }
    });

});

$(window).load(function() {
    $('.preloader-wrapper').fadeOut();
    $('body').removeClass('preloader-site');


});


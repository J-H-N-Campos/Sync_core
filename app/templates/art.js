
function art_check_menu(id_target)
{
    var subtarget = $("#menu-target-" + id_target);
    var menu      = $("[menu-target=" + id_target + "]");
    var nav       = $(".art-main-nav");
    
    //Retira todos
    $(nav).find('a').removeClass('checked');
    $('.art-main-subnav').hide();
    
    //Check atual
    $(menu).addClass('checked');
    
    //Somente se ele achar
    if(subtarget.length)
    {
        //Somente abre se o pai esta aberto
        if($('.art-main-nav').is(':visible'))
        {
            $(".art-main-subnav-content").show();
        }
        
        //Mostra o submenu
        $(subtarget).show();
    }
    else
    {
        $(".art-main-subnav-content").hide();
    }

}

function art_hide_menu()
{
    $('.art-main-nav').hide();
    $('.art-main-subnav-content').hide();
}

function art_check_submenu(id_target)
{
    var menu        = $("[submenu-target=" + id_target + "]");
    var nav         = $(".art-main-subnav");
    var parent      = $(menu).parent();
    var parent_attr = $(parent).attr('id');
    
    //Desmarca todos
    $(nav).find('a').removeClass('checked');
    
    //Check atual
    $(menu).addClass('checked');
    
    if(parent_attr)
    {
        var target_id = parent_attr.replace('menu-target-', '');
        
        //Show menu
        art_check_menu(target_id);
    }
    else
    {
        $(".art-main-subnav-content").hide();
    }
}

function art_expand_menu(status)
{
    //Fecha
    if($('.art-main-nav').is(':visible') || status == 'close')
    {
        $('.art-main-subnav-content').hide();
        $('.art-main-nav').hide();
        $('.art-live-content').show();
        $('.art-main-subnav').hide();
        
        $('#art-toggle-menu').find('i').removeClass('mdi-close');
        $('#art-toggle-menu').find('i').addClass('mdi-menu');
 
    }
    //Abre
    else
    {
        $('.art-main-subnav-content').show();
        $('.art-main-nav').show();
        $('.art-live-content').hide();
        
        $('#art-toggle-menu').find('i').removeClass('mdi-menu');
        $('#art-toggle-menu').find('i').addClass('mdi-close');
        
        //Procura para qual menu esta check
        var id_target = $('.art-main-subnav').find('.checked').attr('submenu-target');
        
        if(id_target)
        {
            art_check_submenu(id_target);
        }
    }
}

$(document).on("click", ".art-nav a", function()
{
    var target = $(this).attr('menu-target');
    
    art_check_menu(target);

});

$(document).on("click", ".art-main-subnav a", function()
{
    var target      = $(this).attr('submenu-target');
    var parent      = $(this).parent();
    var parent_attr = $(parent).attr('id');
    
    if(target)
    {
        //Check do sub
        art_check_submenu(target);
    }

});

$(document).on("click", "#art-toggle-menu", function()
{
    art_expand_menu();
});

// $(document).on("mouseenter", ".art-main-subnav a", function()
// {

    
// }).on('mouseout', 'div.elemento', function() {
//     $(this).removeClass('hover');
//   });
  
  
$(document).on('mouseenter', '.art-main-subnav a', function() {
    
    var info_text        = $(this).attr('info');
    var parent           = $(this).parent();
    var all_content_info = $('.art-main-subnav-info').hide();
    var all_content_info = $('.art-main-subnav-info').html('');
    
    var content_info = $(parent).find('.art-main-subnav-info');
    
    if(info_text)
    {
        var content_info = $(parent).find('.art-main-subnav-info');
        $(content_info).show();
        $(content_info).html(info_text);
    }
    
}).on('mouseout', '.art-main-subnav a', function() {
    
    var info_text        = $(this).attr('info');
    var parent           = $(this).parent();
    var all_content_info = $('.art-main-subnav-info').hide();
    var all_content_info = $('.art-main-subnav-info').html('');
    
    var content_info = $(parent).find('.art-main-subnav-info');
    $(content_info).hide();
});


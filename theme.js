const themToggler = $('#theme-toggle');
localStorage.setItem('theme','light');
$(themToggler).click(function(){
     let theme = localStorage.getItem('theme');
     console.log(theme);
     if(theme == 'light' || theme ==''){
     $(document.documentElement).attr('data-theme', 'dark');
   
          localStorage.setItem('theme','dark');
     }
     else {
          $(document.documentElement).removeAttr('data-theme');
          localStorage.setItem('theme','light');
     }
})
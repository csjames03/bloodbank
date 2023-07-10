if(document.getElementById("button")){
    document.getElementById("button").addEventListener("click",function(){
        document.getElementById('file-input').click();
     })
     document.getElementById("button").addEventListener("onChange", function(){
        document.getElementById('val').text(this.value.replace(/C:\\fakepath\\/i, ''))
     })
}

 
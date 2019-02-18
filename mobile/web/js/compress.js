    function conduct(element,container){
         //    用于压缩图片的canvas
        var canvas = document.createElement("canvas");
        var ctx = canvas.getContext('2d');
        //    瓦片canvas
        var tCanvas = document.createElement("canvas");
        var tctx = tCanvas.getContext("2d");
        console.log(element);
        element.bind('change',function(){
            var files = Array.prototype.slice.call(this.files);
            files.forEach(function(file,i){
                if(!/\/(jpeg|png|gif)/i.test(file.type)){
                    alert(file.type);
                }

                var reader = new FileReader();
                var li = document.createElement("li");
                li.innerHTML = "<div class='progress'><span>"+~~(file.size/1024)+'KB'+"</span></div>";
                container.append($(li));

                reader.onload = function(){
                    var result = this.result;
                    var img = new Image();
                    img.src = result;
                    if(result.length <=102400){
                        $(li).css("background-image","url("+result+")");
                        img = null;
                        upload(result,file.type,$(li));
                        console.log(upload(result,file.type,$(li)));
                    }

                    if(img.complete){
                        callback();
                    }else{
                        img.onload = callback;
                    }

                    function callback(){
                        var data = compress(img);
                        $(li).css("background-image","url("+data+")");
                        upload(result,file.type,$(li));
                        console.log(upload(data,file.type,$(li)));
                        img = null;
                    }

                };
                reader.readAsDataURL(file);
            })
        });
    

    // 压缩
    function compress(img) {
        var initSize = img.src.length;
        var width = img.width;
        var height = img.height;

        //如果图片大于四百万像素，计算压缩比并将大小压至400万以下
        var ratio;
        if ((ratio = width * height / 4000000)>1) {
            ratio = Math.sqrt(ratio);
            width /= ratio;
            height /= ratio;
        }else {
            ratio = 1;
        }

        canvas.width = width;
        canvas.height = height;

    //        铺底色
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        //如果图片像素大于100万则使用瓦片绘制
        var count;
        if ((count = width * height / 1000000) > 1) {
            count = ~~(Math.sqrt(count)+1); //计算要分成多少块瓦片

    //            计算每块瓦片的宽和高
            var nw = ~~(width / count);
            var nh = ~~(height / count);

            tCanvas.width = nw;
            tCanvas.height = nh;

            for (var i = 0; i < count; i++) {
                for (var j = 0; j < count; j++) {
                    tctx.drawImage(img, i * nw * ratio, j * nh * ratio, nw * ratio, nh * ratio, 0, 0, nw, nh);

                    ctx.drawImage(tCanvas, i * nw, j * nh, nw, nh);
                }
            }
        } else {
            ctx.drawImage(img, 0, 0, width, height);
        }

        //进行最小压缩
        var ndata = canvas.toDataURL("image/jpeg", 0.7);

        console.log("压缩前：" + initSize);
        console.log("压缩后：" + ndata.length);
        console.log("压缩率：" + ~~(100 * (initSize - ndata.length) / initSize) + "%");

        tCanvas.width = tCanvas.height = canvas.width = canvas.height = 0;

        return ndata;
    }

 //    图片上传，将base64的图片转成二进制对象，塞进formdata上传
    function upload(basestr, type, $li) {
        var text = window.atob(basestr.split(",")[1]);
        var buffer = new ArrayBuffer(text.length);
        var ubuffer = new Uint8Array(buffer);
        var pecent = 0 , loop = null;

        for (var i = 0; i < text.length; i++) {
            ubuffer[i] = text.charCodeAt(i);
        }

        var Builder = window.WebKitBlobBuilder;
        var blob;

        if (Builder) {
            var builder = new Builder();
            builder.append(buffer);
            blob = builder.getBlob(type);
        } else {
            blob = new window.Blob([buffer], {type: type});
        }

        var formdata = new FormData();
        formdata.append("imagefile", blob);
        return formdata;

    }
    // 获取blob对象的兼容性写法
    function getBlob(buffer, format){
        var Builder = window.WebKitBlobBuilder || window.MozBlobBuilder;
        if(Builder){
            var builder = new Builder;
            builder.append(buffer);
            return builder.getBlob(format);
        } else {
            return new window.Blob([ buffer ], {type: format});
        }
    }

}

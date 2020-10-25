function findGetParameter(parameterName) {
  var result = null,
      tmp = [];
  var items = location.search.substr(1).split("&");
  for (var index = 0; index < items.length; index++) {
    tmp = items[index].split("=");
    if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
  }
  return result;
}

document.addEventListener('DOMContentLoaded', () => {
  VK.init(function() {
    VK.callMethod("setTitle", "Промокоды и купоны, Январь 2020");

    VK.api("users.get", {"user_ids": findGetParameter('vk_user_id'), "fields": "photo_50", "v":"5.73"}, function (data) {
      if (data) {
        $.post(document.location.href, { vk_user_id : findGetParameter('vk_user_id'), first_name: data['response'][0]['first_name'], last_name: data['response'][0]['last_name'], photo: data['response'][0]['photo_50']},
            function(data){
              console.log("Data success");
            }).fail(function(){
          console.log("Request failed");
        });
      } else {
        console.log("Uuid failed");
      }
    });

    VK.addCallback('onLocationChanged', function f(location){
      if (location) {
        $.post(document.location.href, { uuid: location, vk_user_id : findGetParameter('vk_user_id')},
            function(data){
              data = JSON.parse(data);
              document.getElementById("button").href = data['redirect'];
            }).fail(function(){
          console.log("Request failed");
        });
      } else {
        console.log("Uuid failed");
      }
    });
  }, function() {
    console.log("VK init failed");
  }, '5.103');

  var flipdown = new FlipDown((new Date().getTime() / 1000) + (86400 * 2) + 1).start()
    .ifEnded(() => {
        console.log('The countdown has ended!');
    });
});
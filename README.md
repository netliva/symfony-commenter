# Symfony İçin Yorum Modülü

Projeleriniz içerisinde kullanmak için yorum scriptidir. 
Jquery, Bootstrap ve Font-awsome'a ihtiyaç duyar.

## Kurulum

```console
$ composer require netliva/symfony-commenter
```

### Bundle'ı aktifleştir

config/bundles.php içerisinde

```php
return [
    // ...
    Netliva\CommentBundle\NetlivaCommentBundle::class => ['all' => true],
];
``` 


Assetleri Projeye Dahil Edin
----------------------------
### Install assets

Aşağıdaki komut ile assets'lerin kurulumunu gerçekleştirin

`$ php bin/console assets:install` 

Bu komut ile; *public/bundles/netlivamedialib* klasörü içerisinde 
oluşan js ve css dosyalarını projenize dahil ediniz.



```javascript
// assets/js/app.js
import '../../public/bundles/netlivacomment/comment.css'
import '../../public/bundles/netlivacomment/comment'
```

## Kullanıcı Entity Sınıf Ayarları

Kullanıcı sınıfınızın `implements`'lerinde `AuthorInterface` ekleyin
ve _toString fonksiyonunu ekleyin

```php

namespace App\Entity;

// ...
use Netliva\CommentBundle\Entity\AuthorInterface;

/**
 * Staff
 */
class Users implements UserInterface, \Serializable, AuthorInterface
{
    // ...
	public function __toString ()
	{
		return $this->getName();
	}
    // ...
}

```
Gerekli ayarları ekleyin;

```yaml
# config/packages/netliva_commenter.yaml
doctrine:
  orm:
    resolve_target_entities:
      Netliva\CommentBundle\Entity\AuthorInterface: App\Entity\Users

```

```yaml
# config/routes/netliva_commenter.yaml
netliva_comment_route:
    resource: '@NetlivaCommentBundle/Resources/config/routing.yaml'
    prefix: /netliva
```


## Kullanma

Yorum alanı eklemek istediğiniz yere aşağıdaki örneklerde olduğu gibi twig fonksiyonunu ekleyin.

commentbox("kanal_tanimi") şeklinde kullanılır. Kanal yorumları gruplandırmaya yarar. 
```twig
// Belli bir sayfanın altında yorumlar
{{ commentbox("page_"~page.id) }}

// Ürün yorumları
{{ commentbox("product_"~product.id) }}

// Chat alanları
{{ commentbox("room_1") }}
```


## Js Events

Aşağıda tetiklenen bazı jquery olaylar listelenmiştir. Bu olaylar oluşuğunda kendi kodlarınızın çalışmasını sağlayabilirsiniz.


### Tetiklenen Olaylar
#### netliva:commenter:init
Yorum gönderme alanı oluştuktan hemen sonra tetiklenir.
```javascript
$(document).on("netliva:commenter:init", function(event, $comment_area, commenter){
    // $comment_area : Yorum alanı jQuery öğesi
    // commenter : Yorum aksiyonlarının yer aldığı javascript nesnesi 
});
``` 
#### netliva:commenter:initline
Yorum satırları oluştuktan hemen sonra tetiklenir.
```javascript
$(document).on("netliva:commenter:initline", function(event, $comment_line, commenter){
    // $comment_line : Yorum satırı jQuery öğesi
    // commenter : Yorum aksiyonlarının yer aldığı javascript nesnesi 
});
``` 
#### netliva:commenter:send:click
Yorum gönderimi anında tetiklenir
```javascript
$(document).on("netliva:commenter:send:click", function(event, $comment_area, commenter){
    // $comment_area : Yorum alanı jQuery öğesi
    // commenter : Yorum aksiyonlarının yer aldığı javascript nesnesi 
});
``` 
#### netliva:commenter:send:complete
Yorum gönderimi tamamlanınca tetiklenir
```javascript
$(document).on("netliva:commenter:send:complete", function(event, $comment_area, jqXHR, textStatus, commenter){
    // $comment_area : Yorum alanı jQuery öğesi
    // jqXHR : jQuery ajax sonucu dönen XMLHttpRequest nesnesi (https://api.jquery.com/jQuery.ajax/#jqXHR) 
    // textStatus : Dönen sonucun durumunu kategorize eden bir dize ("success", "notmodified", "nocontent", "error", "timeout", "abort", or "parsererror")
    // commenter : Yorum aksiyonlarının yer aldığı javascript nesnesi 
});
``` 
#### netliva:commenter:send:success
Yorum gönderim sonucu başarılıysa tetiklenir
```javascript
$(document).on("netliva:commenter:send:success", function(event, $comment_area, response, textStatus, jqXHR, commenter){
    // $comment_area : Yorum alanı jQuery öğesi
    // response : ajax sonrası dönen yanıt
    // jqXHR : jQuery ajax sonucu dönen XMLHttpRequest nesnesi (https://api.jquery.com/jQuery.ajax/#jqXHR) 
    // commenter : Yorum aksiyonlarının yer aldığı javascript nesnesi 
});
``` 
#### netliva:commenter:send:error
Yorum gönderim sonucu hatalıysa tetiklenir
```javascript
$(document).on("netliva:commenter:send:error", function(event, $comment_area, jqXHR, textStatus, commenter){
    // $comment_area : Yorum alanı jQuery öğesi
    // jqXHR : jQuery ajax sonucu dönen XMLHttpRequest nesnesi (https://api.jquery.com/jQuery.ajax/#jqXHR) 
    // commenter : Yorum aksiyonlarının yer aldığı javascript nesnesi 
});
``` 

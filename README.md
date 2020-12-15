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

# Opsiyonel: yorumlara bırakılan ifadeleri özelleştirmek isterseniz aşağıdakileri ekleyin
netliva_comment:
  emotions:
    like :  { emoji: '👍🏼', color: '#8A6749', desc: 'Beğen' }
    love :  { emoji: '❤️',  color: '#DD2E44', desc: 'Muhteşem' }
    haha :  { emoji: '😂', color: '#DD9E00', desc: 'Hahaha' }
    wow :   { emoji: '😮', color: '#DD9E00', desc: 'İnanılmaz' }
    sad :   { emoji: '😔', color: '#DD9E00', desc: 'Üzgün' }
    angry : { emoji: '😡', color: '#DA2F47', desc: 'Kızgın' } 
    # key olarak herhangi bir değer girilebilir
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
{{ commentbox("page_"~page.id, options) }}

// Ürün yorumları
{{ commentbox("product_"~product.id, options) }}

// Chat alanları
{{ commentbox("room_1", options) }}
```

Options değişkeni key value şeklinde bir dizi değişkendir, aşağıdaki keyler ile değerler gönderilebilir;

Key | Type | Varsayılan | Açıklama
--- | --- | --- | --- |
predefined_texts | array | [] | Ön tanımlı metinler tanımlamanızı sağlar. Mesaj yazarken bu metinler arasından seçilebilmesi sağlanır.
collaborators | boolean | true | Katılımcı alanının aktifliğinin ayarlanmasını sağlar. `false` belirlenirse yorum alanı altındaki katılımcılar alanı gösterilmez 
reactions | boolean | true | Yorumlara ifade bırakma özelliğinin aktifliğini sağlar



## Symfony Events

Aşağıda tetiklenen bazı symfony event'ler listelenmiştir. Bu olaylar oluşuğunda kendi kodlarınızın çalışmasını sağlayan subscriber'lar yazabilirsiniz.


### Tetiklenen Olaylar

Event Key & Class | Descriptions
--- | ---
NetlivaCommenterEvents::AFTER_ADD `Netliva\CommentBundle\Event\AfterAddCommentEvent` | Yorum eklendikten sonra çalışır
NetlivaCommenterEvents::AFTER_ADD_COLLABORATOR `Netliva\CommentBundle\Event\AfterAddCollaboratorsEvent` | Katılımcı eklendikten sonra çalışır
NetlivaCommenterEvents::COMMENT_BOX `Netliva\CommentBundle\Event\CommentBoxEvent` | Yorum eklendikten sonra çalışır
NetlivaCommenterEvents::AFTER_REACTION `Netliva\CommentBundle\Event\AfterAddReactionEvent` | İfade bıraktıktan sonra çalışır
NetlivaCommenterEvents::USER_IMAGE `Netliva\CommentBundle\Event\UserImageEvent` | Kullanıcının profil fotoğrafına ulaşmak istendiğinde çalışır


### Subscribe Oluşturma

```yaml
# aşağıdaki kodu service dosyanıza ekleyin 
services:
    # ...
    my_comment_box_user_image_subscriber:
        class: App\EventListener\CommentBoxUserImageSubscriber
        arguments: [ "@service_container", "@doctrine.orm.entity_manager", "@security.token_storage" ]
        tags:
            - { name: kernel.event_subscriber }

```

```php 

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Netliva\CommentBundle\Event\NetlivaCommenterEvents;
use Netliva\CommentBundle\Event\UserImageEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CommentBoxUserImageSubscriber implements EventSubscriberInterface
{
    public function __construct () { }

    public static function getSubscribedEvents ()
    {
        return [
            NetlivaCommenterEvents::USER_IMAGE => 'getUserImage'
        ];
    }

    public function getUserImage (UserImageEvent $event)
    {
        // Kullanıcı mülküne ulaşın
        $user = $event->getAuthor();

        // kullanıcının profil fotoğrafının resmine ulaşın
        $imgPath = $user->getPhoto();

        if (!$imgPath || !file_exists($imgPath))
            return null;

        // fotoğrafın yolunu set edin, böylece gerekli yerlerde kullanıcı fotoğrafları gösterilir
        $event->setImage($imgPath);
    }

}

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

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

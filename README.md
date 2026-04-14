<p align="center">
</p>

# MelancesCustomSpawner

[Türkçe](#türkçe) | [English](#english)

---

## Türkçe

MelancesCustomSpawner, PocketMine-MP (PMMP 5.x) için geliştirilmiş, vanilla mob spawner olmayan **blok bazlı özel spawner** sistemidir.  
Mob spawnlamak zorunda değildir; doğrudan **item drop** üretir.  
Stack / level mantığıyla çalışır ve performans için yalnızca yakında oyuncu varken aktiftir.

---

## Özellikler
- Blok bazlı özel spawner sistemi
- Mob yerine item drop üretimi
- Stack / level sistemi (aynı blok üzerinde)
- Sağ tık ile level artırma
- Performans odaklı (yakın oyuncu şartı)
- Dünya bazlı kalıcı kayıt
- FloatingTextParticle hologram
- Güçlü dupe ve güvenlik önlemleri

---

## Kurulum
1. Plugin’i `plugins/` klasörüne koy
2. Sunucuyu **bir kez başlat**
3. Sunucuyu kapat
4. Config dosyalarını düzenle
5. Sunucuyu tekrar başlat

---

## Config Ayarlama

Config dosyası yolu:

```text
plugins/MelancesCustomSpawner/config.yml
```

---

## Genel Ayarlar

```yml
settings:
  active_radius: 16
```

- `active_radius`: Spawner’ın çalışması için gerekli oyuncu mesafesi (block)
- Yakında oyuncu yoksa spawner çalışmaz

---

## Hologram Ayarları

```yml
hologram:
  enabled: true
  resend_ticks: 40
  format: "{display} | {level} adet"
```

- `enabled`: Hologram açık / kapalı
- `resend_ticks`: Kaç tick’te bir yenileneceği
- `format`: Spawner üstünde görünen yazı

---

## Spawner Türleri ve Drop Örnekleri

Spawnerlar `spawners:` altında tanımlanır.

### Örümcek Spawner

```yml
spawners:
  orumceksp:
    display: "Örümcek"
    baseIntervalSeconds: 30
    baseAmount: 1
    amountPerLevel: 1
    intervalMultiplierPerLevel: 0.95
    drops:
      - id: "string"
        amount: 1
        weight: 80
      - id: "spider_eye"
        amount: 1
        weight: 20
```

---

### Zombi Spawner

```yml
spawners:
  zombisp:
    display: "Zombi"
    baseIntervalSeconds: 30
    baseAmount: 1
    amountPerLevel: 1
    intervalMultiplierPerLevel: 0.95
    drops:
      - id: "rotten_flesh"
        amount: 1
        weight: 100
```

---

## Weighted Drop Sistemi

- `weight` değeri **oran bazlıdır**
- Yüksek `weight` = daha yüksek drop ihtimali
- Yüzde olmak zorunda değildir

Örnek:

```text
80 + 20 = 100
```

---

## Mesajlar (messages.yml)

Dosya yolu:

```text
plugins/MelancesCustomSpawner/messages.yml
```

```yml
prefix:
  success: "&7[&a+&7] &6MCS &7> &f"
  error: "&7[&c!&7] &6MCS &7> &f"
  info: "&7[&b?&7] &6MCS &7> &f"

runtime:
  no_permission:
    - "Bu komut sana kapalı {player}."
    - "Yetkin yok {player}."
    - "OP + izin olmadan olmaz {player}."
    - "Bu komut sende çalışmaz {player}."
```

Her mesaj için **4 varyasyon** kullanılır ve rastgele seçilir.

---

## Komutlar
- `/spver <kullanıcıadı> <sptürü> <adet>`
- `/splistesi`

---

## Yetkiler

Komutları kullanmak için **hem OP hem de permission gerekir**.

**OP**
```text
pocketmine.group.operator
```

**Komut Yetkileri**
```text
melancescustomspawner.spver
melancescustomspawner.splistesi
```

**Spawner Kırma**
```text
melancescustomspawner.breakany
```

---

## English

MelancesCustomSpawner is a block-based **custom spawner** system for PocketMine-MP (PMMP 5.x).  
It generates **item drops** instead of mobs and only works when a player is nearby for performance.

---

## Features
- Block-based custom spawner system
- Item drops instead of mobs
- Stack / level system
- Right-click to increase level
- Performance optimized (nearby player required)
- Persistent per-world storage
- FloatingTextParticle hologram

---

## Installation
1. Put the plugin into the `plugins/` folder
2. Start the server once
3. Stop the server
4. Edit the config files
5. Start the server again

---

## Configuration

Config path:

```text
plugins/MelancesCustomSpawner/config.yml
```

```yml
settings:
  active_radius: 16
```

---

## Spawner Example

```yml
spawners:
  zombisp:
    display: "Zombie"
    baseIntervalSeconds: 30
    baseAmount: 1
    amountPerLevel: 1
    intervalMultiplierPerLevel: 0.95
    drops:
      - id: "rotten_flesh"
        amount: 1
        weight: 100
```

---

## Permissions

Commands require **BOTH operator and permission**.

```text
pocketmine.group.operator
melancescustomspawner.spver
melancescustomspawner.splistesi
```

---

## License
MIT  
© Melances

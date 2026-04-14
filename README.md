# WHMCS QNB Finansbank e-Fatura & e-Arşiv Modülü

![WHMCS Version](https://img.shields.io/badge/WHMCS-9.0.2-orange)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4%20%7C%208.1-blue)
![License](https://img.shields.io/github/license/Ens-Dev/whmcs-qnbefatura)
![Status](https://img.shields.io/badge/Status-Active-success)

Bu eklenti, WHMCS 9.0.2 altyapısı üzerinden **QNB Finansbank (e-Finans)** sistemine tam entegre çalışarak, ödenen faturalarınızın otomatik olarak e-Fatura veya e-Arşiv olarak resmileştirilmesini sağlar.

## 🚀 Özellikler

* **Otomatik Gönderim:** Müşteri faturasını ödediği anda (`InvoicePaid` kancası ile) fatura arka planda QNB sistemine iletilir.
* **Akıllı Mükellef Sorgulama:** Müşterinin TCKN/VKN bilgisi üzerinden e-Fatura mükellefi olup olmadığı otomatik sorgulanır.
* **Dinamik Fatura Tipi:** Mükellefiyet durumuna göre **e-Fatura** veya **e-Arşiv** kesimi otomatik olarak seçilir.
* **Çift Gönderim Koruması:** Resmileşen faturaların WHMCS notlarına `QNB UUID` numarası işlenir, böylece aynı faturanın yanlışlıkla iki kez gönderilmesi engellenir.
* **Gelişmiş Vergi Hesaplama:** Fatura kalemlerindeki tutarlar ve KDV (0015) oranları otomatik ayrıştırılır.
* **Manuel Test ve Gönderim:** Admin panelindeki eklenti arayüzünden, istenilen faturanın ID numarası girilerek manuel tetikleme yapılabilir.
* **Test Ortamı (Sandbox):** Ayarlardan "Test Mode" aktif edilerek canlıya geçmeden önce güvenle test yapılabilir.

---

## 🛠️ Kurulum Adımları

### 1. Dosyaların Yüklenmesi
1. Bu repodaki dosyaları bilgisayarınıza indirin.
2. Eklentinin çalışabilmesi için klasör adının `qnbefatura` olması gereklidir.
3. Dosyaları WHMCS dizininizdeki `/modules/addons/qnbefatura/` yoluna yükleyin.
4. *(Önemli)* Projenin EFINANS kütüphanesini kullanabilmesi için gerekli bağımlılıkların (Vendor) yüklü olduğundan emin olun.

### 2. Müşteri Özel Alanlarının (Custom Fields) Ayarlanması
Modülün vergi numarasını ve dairesini çekebilmesi için WHMCS panelinizde şu özel alanların oluşturulmuş olması **zorunludur**:

* **Kurulum (Setup)** > **Müşteri Özel Alanları (Custom Client Fields)** sekmesine gidin.
* Aşağıdaki iki alanı birebir aynı isimlerle oluşturun:
  1. Alan Adı: `TKCN/VKN` (Metin Kutusu)
  2. Alan Adı: `Vergi Dairesi` (Metin Kutusu)
* Bu alanları "Faturada Göster" olarak işaretleyebilirsiniz.

### 3. Modülün Aktifleştirilmesi ve Ayarlar
1. WHMCS Admin paneline giriş yapın.
2. **Sistem Ayarları (System Settings)** > **Eklenti Modülleri (Addon Modules)** sayfasına gidin.
3. **QNB e-Invoice & e-Archive** modülünü bulup **Activate (Aktifleştir)** butonuna tıklayın.
4. **Configure (Yapılandır)** butonuna basarak yetkileri (Full Administrator vb.) verin.
5. Aynı ekranda bulunan şu API bilgilerinizi doldurun:
   * **API Username:** QNB e-Finans kullanıcı adınız
   * **API Password:** QNB e-Finans şifreniz
   * **Company Tax ID (VKN):** Kendi şirketinizin vergi numarası
   * **Test Mode:** Kurulum aşamasındaysanız test ortamı için bu kutucuğu işaretleyin.

---

## 💻 Kullanım ve Test

Modül arka planda tam otomatik çalışır. Ancak manuel test yapmak veya hata veren bir faturayı tekrar göndermek isterseniz:

1. **Eklentiler (Addons)** menüsünden **QNB e-Invoice & e-Archive** sayfasına girin.
2. Karşınıza çıkan **"QNB Test Management"** ekranındaki kutucuğa, göndermek istediğiniz faturanın ID numarasını (Örn: 105) yazın.
3. **Send Test Invoice** butonuna tıklayın.
4. İşlem sonucu ve olası API hataları **Sistem Kayıtları (Activity Log)** bölümüne `QNB Lookup Error` veya `QNB E-Invoice Sent` şeklinde kaydedilecektir.

---

## ⚠️ Hata Ayıklama (Troubleshooting)

Eğer faturalar gitmiyorsa:
* WHMCS **Sistem Kayıtları (Activity Log)** bölümünü kontrol edin. Modül, gönderim sırasındaki tüm hataları (Critical Error, Lookup Error) buraya yazmaktadır.
* Müşterinin profiline gidip `TKCN/VKN` alanının doğru ve eksiksiz doldurulduğundan emin olun (Modül 11 haneden kısa ise bireysel müşteri, uzun ise kurumsal müşteri olarak algılar).
* Giden faturaların "Notlar" (Notes) sekmesinde `QNB UUID:` kodunun oluşup oluşmadığını teyit edin.

---

## 📄 Lisans

Bu proje, açık kaynaklı [MIT Lisansı](LICENSE) altında sunulmaktadır. Dilediğiniz gibi kullanabilir ve geliştirebilirsiniz.

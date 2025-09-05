#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <RTClib.h>
#include <LiquidCrystal_I2C.h>

// Konfigurasi AccessPoint
const char* ssid = "SSID"; // Ganti dengan SSID WiFi Anda
const char* password = "PASSWORD"; // Ganti dengan Password WiFi Anda

// IP Static (ESP32) ke AP
IPAddress local_IP(192, 168, 2, 180);
IPAddress gateway(192, 168, 2, 1);
IPAddress subnet(255, 255, 255, 0);
IPAddress primaryDNS(8, 8, 8, 8);
IPAddress secondaryDNS(8, 8, 4, 4);

// Pin Ultrasonik
const int trigPin = 5;
const int echoPin = 18;

// Inisialisasi Komponen
RTC_DS3231 rtc;
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Parameter untuk wadah 24cm tinggi dan 600ml volume
const float tinggi_max_cm = 24.0;      // Tinggi maks ke permukaan (24 cm untuk wadah baru)
const float volume_max_liter = 0.6;    // Volume Maksimal (0.6 liter untuk 600 ml)

// Server PHP
const char* serverName = "http://192.168.2.100/capstone/konfig.php"; // Ganti IP server PHP Anda

// PERKIRAAN KOREKSI KALIBRASI untuk rentang 1cm hingga 24cm
// Ini adalah nilai perkiraan. Kalibrasi aktual sangat disarankan untuk akurasi optimal.
// Array ini mengasumsikan offset yang bervariasi tergantung jarak.
// Indeks 0 = koreksi untuk ~1cm, Indeks 1 = koreksi untuk ~2cm, dst.
float koreksi[] = {
  -0.66, -0.32, 0.03, 0.06, 0.73, 0.43, 0.45, 0.15, -0.21, 0.45,
   0.08, 0.05, -0.33, 0.01, -0.04, -0.38, 0.31, 0.35, 0.38, 0.41,
   0.45, 0.49, -0.05, -0.22, -0.09, 0.33, 0.16, 0.59, 0.14, 0.59,
   0.28, 0.24, 0.71, 0.40, 0.89, 0.07, -0.10, 0.68, 0.29, 0.83,
   0.68, 0.29, -0.06, 0.73, 0.12, 0.72, 0.10, 0.72, 0.71, 0.32                                   // 21-24cm
};
const int JUMLAH_KOREKSI = sizeof(koreksi) / sizeof(koreksi[0]);


// Volume terakhir (untuk pengiriman)
float lastVolume = -1;

// Fungsi koreksi jarak berdasarkan kalibrasi
float koreksiJarak(float jarakSensor) {
  // Pembulatan jarakSensor ke cm terdekat untuk mencari indeks koreksi
  int index = round(jarakSensor) - 1; // Jika jarakSensor 1cm, index 0; jika 24cm, index 23.

  if (index >= 0 && index < JUMLAH_KOREKSI) {
    return jarakSensor + koreksi[index];
  } else {
    // Jika jarakSensor di luar rentang koreksi (misal > 24cm atau <= 0cm),
    // kembalikan jarak asli tanpa koreksi.
    return jarakSensor;
  }
}

void setup() {
  Serial.begin(115200);
  Wire.begin();
  lcd.begin(16, 2);
  lcd.backlight();

  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);

  // Inisialisasi RTC
  if (!rtc.begin()) {
    lcd.print("RTC Gagal");
    Serial.println("Couldn't find RTC");
    while (1);
  }
  // rtc.adjust(DateTime(2025, 7, 20, 21, 15, 0)); // Atur waktu RTC saat ini (20 Juli 2025, 21:15 WIB)

  // IP Static
  if (!WiFi.config(local_IP, gateway, subnet, primaryDNS, secondaryDNS)) {
    Serial.println("Gagal atur IP statis");
  }

  // Koneksi WiFi
  WiFi.begin(ssid, password);
  Serial.print("Menghubungkan ke WiFi");
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 20) {
    delay(500);
    Serial.print(".");
    tries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    lcd.clear();
    lcd.print("WiFi Terhubung");
    Serial.println("\nWiFi Terhubung");
    Serial.print("IP ESP32: ");
    Serial.println(WiFi.localIP());
  } else {
    lcd.clear();
    lcd.print("WiFi Gagal");
    Serial.println("Gagal konek WiFi");
  }
}

void loop() {
  // Baca sensor ultrasonik
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  long duration = pulseIn(echoPin, HIGH);
  float jarak_cm = duration * 0.034 / 2.0;
  
  // Gunakan fungsi koreksi jarak
  float jarak_koreksi = koreksiJarak(jarak_cm);

  // Rumus Hitung tinggi air dan volume
  float tinggi_air_cm = constrain(tinggi_max_cm - jarak_koreksi, 0, tinggi_max_cm);
  float volume_liter = (tinggi_air_cm / tinggi_max_cm) * volume_max_liter;

  // Ambil waktu dari RTC (WIB)
  DateTime now = rtc.now() + TimeSpan(0, 7, 0, 0); // Tambah 7 jam untuk WIB
  char waktuBuffer[20];
  sprintf(waktuBuffer, "%04d-%02d-%02d %02d:%02d:%02d",
          now.year(), now.month(), now.day(), now.hour(), now.minute(), now.second());

  // Tampilkan ke LCD
  lcd.setCursor(0, 0);
  lcd.printf("Vol: %.2f L    ", volume_liter); // Menampilkan 2 angka desimal
  lcd.setCursor(0, 1);
  lcd.printf("WIB %02d:%02d", now.hour(), now.minute());

  // Debug Serial
  Serial.printf("Jarak Sensor: %.2f cm | Jarak Koreksi: %.2f cm | Tinggi Air: %.2f cm | Volume: %.2f L | %s\n",
                jarak_cm, jarak_koreksi, tinggi_air_cm, volume_liter, waktuBuffer);

  // Kirim ke server jika perubahan signifikan (0.01 Liter = 10 ml)
  if (abs(volume_liter - lastVolume) >= 0.01 && WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String url = String(serverName) + "?waktu=" + urlencode(waktuBuffer) +
                 "&volume=" + String(volume_liter, 2) + // Mengirim dengan 2 desimal
                 "&ketinggian=" + String(tinggi_air_cm, 2); // Mengirim dengan 2 desimal
    http.begin(url);
    int httpCode = http.GET();
    Serial.print("Status kirim: ");
    Serial.println(httpCode);
    http.end();
    lastVolume = volume_liter;
  }

  delay(2000); // Penundaan 2 detik sebelum pembacaan berikutnya
}

// Fungsi URL encode untuk HTTP
String urlencode(String str) {
  String encoded = "";
  char c;
  char code0, code1;
  for (int i = 0; i < str.length(); i++) {
    c = str.charAt(i);
    if (isalnum(c)) {
      encoded += c;
    } else {
      code0 = (c >> 4) & 0xF;
      code1 = c & 0xF;
      encoded += '%';
      encoded += "0123456789ABCDEF"[code0];
      encoded += "0123456789ABCDEF"[code1];
    }
  }
  return encoded;
}
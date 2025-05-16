import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

interface User {
  id: number;
  adi_soyadi: string;
  email: string;
  cep_telefonu?: string;
  avatar?: string;
  rutbe: string;
  aktif: boolean;
  created_at?: string;
  // Öğrenci alanları
  okulu?: string;
  sinifi?: string;
  grubu?: string;
  ders_gunu?: string;
  ders_saati?: string;
  ucret?: string;
  // Öğretmen alanları
  brans?: string;
}

interface Stats {
  totalStudents: number;
  totalTeachers: number;
  newUsersToday: number;
}

@Component({
  selector: 'app-ogrenci-listesi-sayfasi',
  standalone: false,
  templateUrl: './ogrenci-listesi-sayfasi.component.html',
  styleUrl: './ogrenci-listesi-sayfasi.component.scss',
})
export class OgrenciListesiSayfasiComponent implements OnInit {
  students: User[] = [];
  teachers: User[] = [];
  newUsers: User[] = [];
  isLoading = true;
  activeTab: 'students' | 'teachers' | 'new' = 'students';
  stats: Stats = {
    totalStudents: 0,
    totalTeachers: 0,
    newUsersToday: 0
  };

  constructor(private http: HttpClient, private router: Router) {}

  ngOnInit(): void {
    this.loadUsers();
  }

  setActiveTab(tab: 'students' | 'teachers' | 'new'): void {
    this.activeTab = tab;
  }

  loadUsers(): void {
    this.isLoading = true;
    // LocalStorage veya sessionStorage'dan token'ı al
    let token = '';
    const userStr = localStorage.getItem('user') || sessionStorage.getItem('user');
    if (userStr) {
      const user = JSON.parse(userStr);
      token = user.token || '';
    }

    // Tüm kullanıcıları getiren API'ye istek gönder
    this.http
      .get<any>('./server/api/ogrenciler_listesi.php', {
        headers: { Authorization: `Bearer ${token}` },
      })
      .subscribe({
        next: (response) => {
          if (response.success) {
            // API yanıtından gelen veriyi al
            const users = Array.isArray(response.data) ? response.data : [];

            // Kullanıcıları rütbelerine göre filtrele
            this.students = users.filter(
              (user: User) => user.rutbe === 'ogrenci' && user.aktif
            );

            // Öğretmenleri filtrele
            this.teachers = users.filter(
              (user: User) => user.rutbe === 'ogretmen' && user.aktif
            );

            // Onay bekleyen kullanıcılar
            this.newUsers = users.filter((user: User) => !user.aktif);

            console.log('Yüklenen öğrenciler:', this.students);
            console.log('Yüklenen öğretmenler:', this.teachers);
          } else {
            console.error('API yanıtı başarısız:', response.error);
          }
          this.isLoading = false;
        },
        error: (error) => {
          console.error('API hatası:', error);
          this.isLoading = false;
        },
      });
  }

  loadTeachers(token: string): void {
    this.http
      .get<any>('./server/api/ogretmenler_listesi.php', {
        headers: { Authorization: `Bearer ${token}` }
      })
      .subscribe({
        next: (response) => {
          if (response.success) {
            this.teachers = response.data;
            this.stats.totalTeachers = this.teachers.length;
          }
        },
        error: (error) => {
          console.error('Öğretmen listesi alınırken hata:', error);
        },
        complete: () => {
          this.isLoading = false;
        }
      });
  }

  deleteStudent(id: number): void {
    if (!confirm('Bu öğrenciyi silmek istediğinize emin misiniz?')) {
      return;
    }

    // LocalStorage veya sessionStorage'dan token'ı al
    let token = '';
    const userStr = localStorage.getItem('user') || sessionStorage.getItem('user');
    if (userStr) {
      const user = JSON.parse(userStr);
      token = user.token || '';
    }

    this.http
      .post<any>('./server/api/ogrenci_sil.php', { id }, {
        headers: { 
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      })
      .subscribe({
        next: (response) => {
          if (response.success) {
            alert('Öğrenci başarıyla silindi!');
            // Listeyi yenile
            this.loadUsers();
          } else {
            alert('Silme işlemi başarısız: ' + response.error);
          }
        },
        error: (error) => {
          console.error('API hatası:', error);
          alert('Silme işlemi sırasında bir hata oluştu: ' + (error.message || 'Bilinmeyen bir hata'));
        },
      });
  }

  deleteTeacher(id: number): void {
    if (!confirm('Bu öğretmeni silmek istediğinize emin misiniz?')) {
      return;
    }

    // LocalStorage veya sessionStorage'dan token'ı al
    let token = '';
    const userStr = localStorage.getItem('user') || sessionStorage.getItem('user');
    if (userStr) {
      const user = JSON.parse(userStr);
      token = user.token || '';
    }

    this.http
      .post<any>('./server/api/ogretmen_sil.php', { id }, {
        headers: { 
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      })
      .subscribe({
        next: (response) => {
          if (response.success) {
            alert('Öğretmen başarıyla silindi!');
            // Listeyi yenile
            this.loadUsers();
          } else {
            alert('Silme işlemi başarısız: ' + response.error);
          }
        },
        error: (error) => {
          console.error('API hatası:', error);
          alert('Silme işlemi sırasında bir hata oluştu: ' + (error.message || 'Bilinmeyen bir hata'));
        },
      });
  }

    // Yeni kullanıcıyı onaylama fonksiyonu
    approveUser(userId: number) {
      if (!confirm('Bu kullanıcıyı onaylamak istediğinizden emin misiniz?')) {
        return;
      }

      // LocalStorage veya sessionStorage'dan token'ı al
      let token = '';
      const userStr = localStorage.getItem('user') || sessionStorage.getItem('user');
      if (userStr) {
        const user = JSON.parse(userStr);
        token = user.token || '';
      }

      // Kullanıcı verilerini hazırla
      const userData = {
        id: userId,
        rutbe: 'ogrenci', // Onaylandığında öğrenci olarak ayarla
        aktif: 1 // Aktif hesap olarak ayarla
      };

      this.http
        .post<any>('./server/api/kullanici_guncelle.php', userData, {
          headers: { 
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })
        .subscribe({
          next: (response) => {
            if (response.success) {
              alert('Kullanıcı başarıyla onaylandı!');
              this.loadUsers(); // Kullanıcı listesini yeniden yükle
            } else {
              alert('Kullanıcı onaylanamadı: ' + response.error);
            }
          },
          error: (error) => {
            console.error('Onaylama hatası:', error);
            alert('Onaylama işlemi sırasında bir hata oluştu: ' + (error.message || 'Bilinmeyen bir hata'));
          },
        });
    }

    // Yeni kullanıcıyı reddetme fonksiyonu
    rejectUser(userId: number) {
      if (!confirm('Bu kullanıcıyı reddetmek istediğinizden emin misiniz? Bu işlem kullanıcıyı silecektir.')) {
        return;
      }

      // LocalStorage veya sessionStorage'dan token'ı al
      let token = '';
      const userStr = localStorage.getItem('user') || sessionStorage.getItem('user');
      if (userStr) {
        const user = JSON.parse(userStr);
        token = user.token || '';
      }

      this.http
        .post<any>('./server/api/ogrenci_sil.php', { id: userId }, {
          headers: { 
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })
        .subscribe({
          next: (response) => {
            if (response.success) {
              alert('Kullanıcı başarıyla reddedildi ve silindi!');
              this.loadUsers(); // Kullanıcı listesini yeniden yükle
            } else {
              alert('Kullanıcı reddedilemedi: ' + response.error);
            }
          },
          error: (error) => {
            console.error('Reddetme hatası:', error);
            alert('Reddetme işlemi sırasında bir hata oluştu: ' + (error.message || 'Bilinmeyen bir hata'));
          },
        });
    }
}
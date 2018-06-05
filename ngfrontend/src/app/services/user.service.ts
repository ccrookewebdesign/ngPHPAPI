import { APIResponse } from './user.service';
import { Injectable } from '@angular/core';
import { Http, Response, Headers, RequestOptions } from '@angular/http';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, tap, catchError } from 'rxjs/operators';

import { environment } from '../../environments/environment';

export interface User {
  id: number;
  username: string;
  firstname: string;
  lastname: string;
  email: string;
  password: string;
  lastlogin?: string;
  create_dt?: string;
}

export interface APIResponse {
  success: string;
  message: string | User;
  errcode?: string;
  token?: string;
  data?: Object;
}

@Injectable()
export class UserService {
  public jwtToken: string;
  public currentUser: User = {
    id: 0,
    username: '',
    firstname: '',
    lastname: '',
    email: '',
    password: '',
    lastlogin: ''
  };
  public userMessage: string = '';

  constructor(private http: HttpClient) {
    const theUser: any = JSON.parse(localStorage.getItem('currentUser'));

    if (theUser) {
      this.jwtToken = theUser.token;
    }
  }

  login(oUser): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/json' })
    };

    return (
      this.http
        //.post(environment.apiUrl + 'login', oUser, httpOptions)
        .post(environment.apiUrl + 'login.php', oUser, httpOptions)
        .pipe(
          tap((response: APIResponse) => {
            if (response.success) {
              this.currentUser = <User>response.data;
              const userObj: any = {};
              userObj.user = response.data;
              userObj.token = response.token;
              this.jwtToken = response.token;

              localStorage.setItem('currentUser', JSON.stringify(userObj));
            }
          }),
          catchError(this.handleError)
        )
    );
  }

  getUser(id): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({
        'Content-Type': 'application/json',
        Authorization: `${this.jwtToken}`
      })
    };
    return this.http
      .get(`${environment.apiUrl}get.php?id=${id}`, httpOptions)
      .pipe(catchError(this.handleError));
  }

  getUsers(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({
        'Content-Type': 'application/json',
        Authorization: `${this.jwtToken}`
      })
    };
    return this.http
      .get(`${environment.apiUrl}getall.php`, httpOptions)
      .pipe(catchError(this.handleError));
  }

  insertUser(oUser): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/json' })
    };

    return this.http
      .post(environment.apiUrl + 'insert.php', oUser, httpOptions)
      .pipe(
        tap((response: APIResponse) => {
          //3console.log(response);
          if (response.success) {
            this.currentUser = <User>response.data;
            const userObj: any = {};
            userObj.user = response.data;
            userObj.token = response.token;
            this.jwtToken = response.token;
            localStorage.setItem('currentUser', JSON.stringify(userObj));
          }
        }),
        catchError(this.handleError)
      );
  }

  updateUser(id, oUser): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({
        'Content-Type': 'application/json',
        Authorization: `${this.jwtToken}`
      })
    };

    return this.http
      .put(`${environment.apiUrl}update.php?id=${id}`, oUser, httpOptions)
      .pipe(catchError(this.handleError));
  }

  deleteUser(id): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({
        'Content-Type': 'application/json',
        Authorization: `${this.jwtToken}`
      })
    };

    return this.http
      .delete(`${environment.apiUrl}delete.php?id=${id}`, httpOptions)
      .pipe(catchError(this.handleError));
  }

  logout(): void {
    this.currentUser = {
      id: 0,
      username: '',
      firstname: '',
      lastname: '',
      email: '',
      password: '',
      lastlogin: ''
    };

    localStorage.removeItem('currentUser');

    console.log('');
    console.log('logout');
    console.log(this.currentUser);
  }

  loggedIn(): boolean {
    return !!this.currentUser.id;
  }

  private handleError(error: Response) {
    console.error('handleerror: ');
    console.error(error);
    return of(error || 'Server error');
  }
}

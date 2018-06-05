import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, ActivatedRouteSnapshot } from '@angular/router';
import {
  FormBuilder,
  FormGroup,
  Validators,
  AbstractControl
} from '@angular/forms';
import { Router } from '@angular/router';

import { Observable } from 'rxjs';
import { map, tap, catchError, switchMap } from 'rxjs/operators';

import { UserService, User } from './../services/user.service';

function passwordConfirm(c: AbstractControl): any {
  if (!c.parent || !c) return;

  const pwd = c.parent.get('password');
  const cpwd = c.parent.get('confirmpassword');

  if (!pwd || !cpwd) return;

  if (pwd.value.trim() !== cpwd.value.trim()) {
    return { invalid: true };
  }
}

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.scss']
})
export class ProfileComponent implements OnInit {
  user: User;
  id: number;
  hide = true;
  pageHeader = 'Register';
  private sub: any;

  message: string;
  disableForm: boolean = false;

  profileForm = this.fb.group({
    username: [
      '',
      [Validators.required, Validators.minLength(5), Validators.maxLength(16)]
    ],
    firstname: ['', Validators.required],
    lastname: ['', [Validators.required]],
    email: ['', [Validators.required, Validators.email]],
    password: [
      '',
      [
        Validators.required,
        Validators.pattern(
          '^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[!@#$%^&*(),.?":{}|<>]).{8,16}$'
        )
      ]
    ],
    confirmpassword: ['', [Validators.required, passwordConfirm]]
  });

  constructor(
    private route: ActivatedRoute,
    private userService: UserService,
    private fb: FormBuilder,
    private router: Router
  ) {}

  ngOnInit() {
    this.sub = this.route.params.subscribe(params => {
      this.id = +params['id'];
    });

    if (this.id) {
      this.userService.getUser(this.id).subscribe(data => {
        if (data.success) {
          this.user = JSON.parse(data.data);
          this.pageHeader = this.user.username + ' Details';
          this.populateForm(this.user);
        } else if (data.errcode.indexOf('jwt') >= 0) {
          this.userService.logout();
          this.userService.userMessage = data.message;
          this.router.navigate(['/login']);
        }
        console.log('');
        console.log('profile OnInit getUser');
        console.log(data);
      });
    }

    this.profileForm.valueChanges
      .pipe(
        map(value => {
          value.firstname = value.firstname.trim();
          value.lastname = value.lastname.trim();
          value.username = value.username.trim();
          value.email = value.email.trim();
          value.password = value.password.trim();
          value.confirmpassword = value.confirmpassword.trim();
          return value;
        })
      )
      .subscribe(val => {
        this.disableForm = false;
        this.message = '';
      });
  }

  populateForm(data): void {
    this.profileForm.patchValue({
      username: data.username,
      firstname: data.firstname,
      lastname: data.lastname,
      email: data.email,
      password: data.password,
      confirmpassword: data.password,
      lastlogin: data.lastlogin
    });
  }

  onSubmit(): void {
    this.message = '';
    if (this.profileForm.dirty && this.profileForm.valid) {
      if (this.id) {
        this.profileForm.value.id = this.id;

        this.userService
          .updateUser(this.id, this.profileForm.value)
          .subscribe(data => {
            console.log('');
            console.log('profile onSubmit updateUser:');
            console.log(data);

            if (data.success) {
              if (this.id === this.userService.currentUser.id) {
                const theUser: any = JSON.parse(
                  localStorage.getItem('currentUser')
                );
                localStorage.setItem('currentUser', JSON.stringify(theUser));
              }
              this.router.navigate(['']);
            } else {
              this.message = data.message;
            }
          });
      } else {
        //console.log(this.profileForm.value);
        this.userService.insertUser(this.profileForm.value).subscribe(data => {
          console.log('');
          console.log('profile onSubmit insertUser:');
          console.log(data);

          if (data.success) {
            /* const theUser: any = JSON.parse(
              localStorage.getItem('currentUser')
            );

            localStorage.setItem('currentUser', JSON.stringify(theUser)); */

            this.router.navigate(['']);
          } else {
            this.message = data.message;
          }
        });
      }
    }
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }
}

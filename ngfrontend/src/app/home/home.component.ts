import { Component, OnInit, ViewChild } from '@angular/core';
import { MatPaginator, MatSort, MatTableDataSource } from '@angular/material';
import { Router } from '@angular/router';

import { UserService, User } from './../services/user.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.scss']
})
export class HomeComponent implements OnInit {
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;

  users: User[];
  displayedColumns = ['firstname', 'lastname', 'username', 'email', 'delete'];
  dataSource: any; //DataTableDataSource;
  message: string;

  constructor(private userService: UserService, private router: Router) {}

  ngOnInit() {
    this.getUsers();
  }

  getUsers() {
    this.userService.getUsers().subscribe(data => {
      if (data.success) {
        this.users = JSON.parse(data.data);
        this.dataSource = new MatTableDataSource<User>(this.users);
        this.dataSource.paginator = this.paginator;
        this.dataSource.sort = this.sort;
      } else if (data.errcode.indexOf('jwt') >= 0) {
        this.userService.logout();
        this.userService.userMessage = data.message;
        this.router.navigate(['/login']);
      }

      console.log('');
      console.log('home OnInit getUsers:');
      console.log(data);
    });
  }

  deleteUser(id: number): void {
    if (confirm('Do you really want to delete this record?')) {
      this.userService.deleteUser(id).subscribe(data => {
        console.log('');
        console.log('home deleteUser:');
        console.log(data);

        if (data.success) {
          //this.router.navigate(['']);
          this.message = data.message;
          this.getUsers();
        }
      });
    }
  }

  applyFilter(filterValue: string) {
    filterValue = filterValue.trim();
    filterValue = filterValue.toLowerCase();
    this.dataSource.filter = filterValue;
  }
}

## REST File handler

### Get file listing

```sh
$ curl -v localhost:8080
```

### Download an existing file

```sh
$ curl -v localhost:8080/innovate-or-die.png > innovate-or-die.png
```

### Upload a file via using multipart/form-data

```sh
$ curl -v -F "file=@enter.ini;type=text/plain" localhost:8080
```

### Delete the file

```sh
$ curl -v -XDELETE localhost:8080/enter.ini
```
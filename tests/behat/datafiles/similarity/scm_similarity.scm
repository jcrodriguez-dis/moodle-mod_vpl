#lang racket/base

(require errortrace)
(require racket/trace)

(define (fibo n)
    (cond
        ((or (= n 0) (= n 1)) n)
        ((> n 1) (+ (fibo (- n 1)) (fibo (- n 2))))
        (else -1)
    )
)

(define (expo b n)
    (cond
        ((= n 0) 1)
        ((>= n 1) (* b (expo b (- n 1))))
        (else -1)
    )
)

(define (min numbers)
    (define (rec_min numbers num_min)
        (cond
            ((null? numbers) num_min)
            ((> num_min (car numbers)) (rec_min (cdr numbers) (car numbers)))
            (else (rec_min (cdr numbers) num_min))
        )
    )
    (cond
        ((null? numbers) '())
        (else (rec_min (cdr numbers) (car numbers)))
    )
)

(define (insert num numbers)
    (cond
        ((null? numbers) (list num))
        ((<= num (car numbers)) (cons num numbers))
        (else (cons (car numbers) (insert num (cdr numbers))))
    )
)

(define (concat L1 L2)
    (cond
        ((null? L1) L2)
        ((null? L2) L1)
        (else (cons (car L1) (concat (cdr L1) L2)))
    )
)

(define (invert numbers)
    (cond
        ((null? numbers) '())
        ((null? (cdr numbers)) (list (car numbers)))
        (else (concat (invert (cdr numbers)) (list (car numbers))))
    )
)

(define (delete E L)
    (cond
        ((null? L) '())
        ((equal? (car L) E) (delete E (cdr L)))
        (else (cons (car L) (delete E (cdr L))))
    )
)

(define (repeated L)
    (define (count E L)
        (cond
            ((null? L) 0)
            ((not (equal? E (car L))) (count E (cdr L)))
            (else (+ 1 (count E (cdr L))))
        )
    )
    (define (unique E L)
        (cond
            ((null? L) '())
            (else (let
                ((num (- (count E L) 1)))
                (cond
                    ((<= num 0) L)
                    (else (cond
                        ((equal? (car L) E) (unique E (cdr L)))
                        (else (cons (car L) (unique E (cdr L))))
                    ))
                )
            ))
        )
    )
    (cond
        ((null? L) '())
        (else (let
            ((_L (unique (car L) L)))
            (if (equal? (car L) (car _L))
                (cons (car L) (repeated (cdr _L)))
                (repeated _L)
            )
        ))
    )
)
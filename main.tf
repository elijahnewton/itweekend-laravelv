provider "aws" { region = "us-east-1" }

# 1. Shared Database
resource "aws_db_instance" "lms_db" {
  allocated_storage = 20
  engine            = "postgres"
  instance_class    = "db.t3.micro"
  db_name           = "lms"
  username          = "postgres"
  password          = var.db_password
  skip_final_snapshot = true
}

# 2. Routing (Load Balancer)
resource "aws_lb" "lms_alb" {
  name = "lms-alb"
  load_balancer_type = "application"
  subnets = [aws_subnet.a.id, aws_subnet.b.id]
}

resource "aws_lb_target_group" "blue" { name = "tg-blue"; port = 8000; protocol = "HTTP"; vpc_id = aws_vpc.main.id }
resource "aws_lb_target_group" "green" { name = "tg-green"; port = 8000; protocol = "HTTP"; vpc_id = aws_vpc.main.id }

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.lms_alb.arn
  port = "80"
  default_action {
    type = "forward"
    target_group_arn = aws_lb_target_group.blue.arn # Default to Blue
  }
}